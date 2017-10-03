Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require loconox/entity-routing-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new \Loconox\EntityRoutingBundle\LoconoxEntityRoutingBundle(),
        );

        // ...
    }

    // ...
}
```

Requirements
============

PCRE
----

    $ ./configure --enable-utf --enable-pcre16 --enable-pcre32 --prefix=/opt/local --enable-unicode-properties

PCRE have to be compatible with UTF-8 encoding.
The command above should print at least "UTF-8 support" and "Unicode properties support".

    $ pcretest -C

Configuration
=============

Cmf Routing
-----------

```yaml
cmf_routing:
    chain:
        routers_by_id:
            loconox_entity_routing.router: 100
            router.default: 75
```

Routing bundle
--------------

Optionally, you can specify a different entity manager for your slug to be store and a specific class.

```yaml
loconox_entity_routing:
    entity_manager: default
    class:
        slug: Loconox\EntityRoutingBundle\Entity\Slug
```
