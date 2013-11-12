<?php
require 'vendor/autoload.php';

$config = json_decode(file_get_contents('config.json'), true);

$app = new \Slim\Slim();

$app->get(
		'/' . $config['image_dir'] . ':parts+',
		function($parts) use ($config, $app)
		{
			// Grab the filename
			$filename = array_pop($parts);

			// Grab the derivative
			$derivative = array_shift($parts);

			// Get the path
			$path = implode("/", $parts);

			// assemble the destination path
			$destination_path = $config['image_dir'] . $derivative . '/' . $path;

			// Create the directory, if required
			if (!file_exists($destination_path)) {
			    mkdir($destination_path, 0777, true);
			}

			$source_path = $config['image_dir'] . $path;

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
				}
			}

			if(empty($specs))
			{
				die;
			}

			// Do the transformations	
			try {

				$class = "Imagine\\{$config['library']}\\Imagine";

			    $imagine = new $class;
				
				if( ! empty($specs['width']) && ! empty($specs['height']))
				{
					$image = $imagine->open($source_path . '/' . $filename)
									->thumbnail( new Imagine\Image\Box($specs['width'], $specs['height']), 'outbound')
									->save($destination_path . '/' . $filename);
					
				} else 
				{
					$image = $imagine->open($source_path . '/' . $filename);

					if(! empty($specs['width']))
					{
						$image->resize($image->getSize()->widen( $specs['width'] ));
					} else
					{
						$image->resize($image->getSize()->heighten( $specs['height'] ));
					}

					$image->save($destination_path . '/' . $filename);
				}

			} catch (Imagine\Exception\Exception $e) {
			    // handle the exception
			}

			// set the headers; first, getting the Mime type
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime_type = finfo_file($finfo, $destination_path . '/' . $filename);
			$app->response->headers->set('Content-Type', $mime_type);

			// Get the file extension, so we know how to output the file
			$path_info = pathinfo($filename);
			$ext = $path_info['extension'];

			echo $image;
		}
	);

$app->run();