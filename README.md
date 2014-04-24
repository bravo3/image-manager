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

Examples
--------

### Storing an image

    // Use the local filesystem as a fake remote (replace with S3, etc)
    $im = new ImageManager(new Filesystem(new LocalAdapter('/tmp/images')));

    // Load local "image.png" and give it a key of 'content_123_image_1' (using the filename is suitable too)
    $image = $im->loadFromFile('image.png', 'content_123_image_1');

    // Save it on the remote
    $im->push($image);

### Retrieving an image

    $image = new Image('content_123_image_1');
    $im->pull($image);

    // Save to local filesystem
    $im->save($image, '/tmp/image.png');

    // Output to client
    echo $image->getData();

### Retrieving an automatic variation

    // Define a dimension that the image will fit in a height of 200px
    $dimensions = new ImageDimensions(null, 200);

    // Create a specification for a JPEG format, quality 75% and use the above dimensions
    $image = new ImageVariation('content_123_image_1', ImageFormat::JPEG(), 75, $dimensions);

    // Automatically create the variation when we pull
    $im->pull($image);

    if (!$im->isPersistent()) {
        // Make sure our new variation exists on the remote
        $im->push($image);
    }

    echo $image->getData();

### Create a variation manually

    $source = $im->loadFromFile('image.png', 'content_123_image_1');
    $resized = $im->createVariation($source, ImageFormat::JPEG(), 90, new ImageDimensions(100, 100));
    $im->push($resized);

### Check if a variation exists

    $image = new ImageVariation('content_123_image_1', ImageFormat::JPEG(), 75, $dimensions);

    if (!$im->exists($image)) {
        $im->pull($image);  // Creates the variation
        $im->push($image);  // Save it on the remote
    }

    echo '<img src="http://cdn.example.com/'.$im->getKey().'" />';

Caching
-------

Because remote storage services have a moderate degree of lag while talking to, it's probably not appropriate to do
"exists" checks on every image variation during page generation. To avoid this you can either pre-render the image and
assume it will exist, or using a quick caching mechanic to store an inventory of all images available.

Your caching mechanic MUST be persistent - if a cache key is lost the image manager will assume the remote file does
not exist. Using a database or disk backed key/value storage is recommended (eg Redis).

To use caching, just include a \Bravo3\Cache\PoolInterface implementation in the ImageManager's constructor.

Future Considerations
---------------------

* Allow for image manipulations (eg add text, rotate, etc)
* Allow for customisable variation naming schemes
