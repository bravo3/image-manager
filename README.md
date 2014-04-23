Image Manager
=============

A PHP 5.4 image manager intended for cloud use. This image manager is designed to be low-level and work with 'keys' -
not directly attach to an entity.

Features
--------

* Easily push and pull images to any remote filesystem (eg Amazon S3)
* Request an image with specific dimensions - allow the manager to transparently create & store this variation
* Request that an image dimension exists (will be created if it doesn't), allowing for the storage device to be used as a CDN end-point
* Use a caching service (PSR-6 compliant) to maintain a knowledge base of image dimensions available to improve performance
* Load & save images from memory or a file
* Convert image format & quality with ease

Future Considerations
---------------------

* Allow for image manipulations (eg add text, rotate, etc)

Current State
-------------

### Working

* Load and save from the local filesystem
* Push and pull to a remote filesystem
* Save in another format
* Image variations

### Not Working

* Remote directory caching

