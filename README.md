ImageSizer
==========

A simple application for resizing and caching images on-demand.

Configurable to use either GD2 or ImageMagick libraries.  Can also include preset image sizes in the config file.

##Installation

Application is built on top of Slim and Imagine, so need to install those dependencies first.

Install Composer and run `composer update` on this directory.

Ensure that img/ is writable.

Visit img/640x/uploads/test.jpg in browser.

##Usage

visit img/[derivative]/[path_to_file] e.g. img/640x/uploads/test.jpg

Named derivative - uses the config file.
N - Resizes image to fit into an NxN square whilst maintaining aspect ratio.
Nx - Resizes image to N pixels wide while maintaining aspect ratio.
xN - Resizes image to N pixels tall while maintaining aspect ratio.
N1xN2 - Creates a cropped thumbnail of the image at the given size.