Image Manager
=============

A PHP 5.4 image variation manager intended for cloud storage use.

This library should:

* Allow you to store and retrieve images from any driver you
* Seamlessly integration with major cloud providers via Bravo3/Cloud-Controller
* Use the PSR caching draft as a persistent object storage interface (to be considered)

You should be able to:

* Resample any image on the fly, or ahead of your request
* Manipulate your source image with a variety of operations
* Use your cloud storage as a CDN instead of returning variations
* Return your image variation directly, as a pipe to the client

