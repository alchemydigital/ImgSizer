<?php
require 'vendor/autoload.php';

// Load the config file
$config = json_decode(file_get_contents('config.json'), true);

// Increase the memory limit if necessary
if( ! empty($config['memory_limit']))
{
	// @todo check to see that the current memory limit is lower before setting a new one
	ini_set('memory_limit', $config['memory_limit']);
}

$app = new \Slim\Slim();

$app->get(
		'/' . $config['image_dir'] . ':parts+',
		function($parts) use ($config, $app)
		{
			// Get the filename
			$filename = array_pop($parts);

			// Get the derivative
			$derivative = array_shift($parts);

			// Get the path
			$path = implode("/", $parts);

			// Set the source path
			$source_path = $config['image_dir'] . $path;

			// Does this file exist?
			if ( ! file_exists($source_path)) {
				// @todo Is it an image? e.g. if PDF, maybe create a thumbnail?
				die;
			}

			// Assemble the destination path
			$destination_path = $config['image_dir'] . $derivative . '/' . $path;

			// create the destination directory structure if required
			if ( ! file_exists($destination_path)) {
			    mkdir($destination_path, 0777, true);
			}

			if( ! empty($config['sizes'][$derivative]))
			{
				$specs = $config['sizes'][$derivative];
			} else
			{
				if (strpos($derivative, 'x') !== FALSE)
				{
					list($specs['width'], $specs['height']) = explode('x', $derivative);
				} else if(is_numeric($derivative))
				{
					$specs['width'] = $specs['height'] = $derivative;
					$specs['type'] = 'inset';
				}
			}

			// We don't have any idea how to transform the image - let's bail!
			if(empty($specs))
			{
				// @todo How to handle the lack of specs - serve the original image?
				die;
			}

			// Do the transformation(s)	
			try {

				$class = "Imagine\\{$config['library']}\\Imagine";

			    $imagine = new $class;


			    // Sometimes images are uploaded in the wrong orientation, but the true orientation can sometimes be found in the image EXIF data
			    // Check to see if we can find it (JPEG and TIFF only)
			    $rotateVal = 0;

			    if(in_array(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $source_path . '/' . $filename), array('image/jpeg', 'image/tiff')))
			    {
			    	$exifData = exif_read_data($source_path . '/' . $filename);

			    	switch($exifData['Orientation']) {
					    case 8:
					        $rotateVal = -90;
					        break;
					    case 3:
					        $rotateVal = 180;
					        break;
					    case 6:
					        $rotateVal = 90;
					        break;
					}
			    }
				
				if( ! empty($specs['width']) && ! empty($specs['height']))
				{
					// For some reason you need to chain the entire transformation in one go
					// when using the thumbnail() command - hence the repitition below
					$image = $imagine->open($source_path . '/' . $filename)
									->rotate($rotateVal)
									->thumbnail( new Imagine\Image\Box($specs['width'], $specs['height']), ( ! empty($specs['type'])) ? $specs['type'] : 'outbound')
									->save($destination_path . '/' . $filename);
					
				} else 
				{
					$image = $imagine->open($source_path . '/' . $filename)->rotate($rotateVal);

					if( ! empty($specs['width']))
					{
						$image->resize($image->getSize()->widen( $specs['width'] ));
					} else
					{
						$image->resize($image->getSize()->heighten( $specs['height'] ));
					}

					// Save image for direct access next time
					$image->save($destination_path . '/' . $filename);
				}

			} catch (Imagine\Exception\Exception $e) {
			    // handle the exception
			}

			// Set the headers... 
			// Get the mime type
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime_type = finfo_file($finfo, $destination_path . '/' . $filename);
			$app->response->headers->set('Content-Type', $mime_type);

			// Get the file extension so we know how to output the file
			$path_info = pathinfo($filename);
			$ext = $path_info['extension'];

			// Output the resized image
			echo $image;
		}
	);

$app->run();