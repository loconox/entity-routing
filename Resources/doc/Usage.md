Usage
=====

# For parameters in path

Assuming you have a `Product` class and you want to have the name of products as a slug in your routes:

```php
<?php
class Product
{
    private $name;
    // ...
}
```

Within you `ProductController`, create a route with the new annotation class `Loconox\EntityRoutingBundle\Annotation\Route`

```php
<?php
// ...

use Loconox\EntityRoutingBundle\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ProductController extends Controller
{
    /**
     * @Route("/{product}", name="product")
     */
    public function index($product): void
    {
        // ...
    }
}
```

Create a `ProductSlugService` class and implement the different functions corresponding to your needs.

```php
<?php

namespace App\Slug\Service;


use App\Entity\Product;
use Doctrine\ORM\EntityManager;
use Loconox\EntityRoutingBundle\Model\SlugInterface;
use Loconox\EntityRoutingBundle\Slug\Service\BaseSlugService;

class ProductSlugService extends BaseSlugService
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Get the entity linked to the slug
     *
     * @param SlugInterface $slug
     *
     * @return mixed
     */
    public function getEntity(SlugInterface $slug)
    {
        return $this->em->getRepository(Product::class)->find($slug->getEntityId());
    }

    /**
     * Set the slug of the entity
     *
     * @param Product $entity
     * @param SlugInterface $slug
     */
    public function setEntitySlug(SlugInterface $slug, $entity)
    {
        $entity->setName($slug->getSlug());
    }

    /**
     * Get the slug linked to the entity
     *
     * @param Product $entity
     *
     * @return string
     */
    public function getEntitySlug($entity)
    {
        return $this->slugify($entity->getName());
    }

    /**
     * Returns true if the entity has changed, false otherwise
     *
     * @param $entity
     *
     * @return bool
     */
    public function hasChanged($entity)
    {
        $slug = $this->findSlug($entity);
        $oldSlug = $slug->getSlug();
        $newSlug = $this->getEntitySlug($entity);

        return $oldSlug !== $newSlug;
    }

    /**
     * Returns the entity id
     *
     * @param Product $entity
     *
     * @return mixed
     */
    public function getEntityId($entity)
    {
        return $entity->getId();
    }

    /**
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }
}
```

Declare the service:

```yaml
# /config/services.yaml
services:
    App\Slug\Service\ProductSlugService:
        arguments:
            - "App\\Entity\\Product"
            - "@loconox_entity_routing.manager.slug"
        calls:
            - [ setEntityManager, [ "@loconox_entity_routing.entity_manager" ]]
        tags:
            - { name: loconox_entity_routing.slug.service, alias: product }
```

# For parameters in host

Assuming you have a `Website` class and you want to have the domain attribute as a parameter for your routes:

```php
<?php
class Website
{
    private $domain;
    // ...
}
```

Within you `IndexController`, create a route with the new annotation class `Loconox\EntityRoutingBundle\Annotation\Route`

```php
<?php
// ...

use Loconox\EntityRoutingBundle\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class IndexController extends Controller
{
    /**
     * @Route("/", name="index",  host="{domain}")
     */
    public function index($domain): void
    {
        // ...
    }
}
```

Create a `WebsiteHostService` class and implement the different functions corresponding to your needs.

```php
<?php

namespace App\Host\Service;


use App\Entity\Website;
use Loconox\EntityRoutingBundle\Host\Service\AbstractHostService;

class WebsiteHostService extends AbstractHostService
{
    // ...
    
     /**
         * Find an entity by its hostname field and return it.
         *
         * @param string $domain
         * @return Website|null
         */
        public function findOneByHost($domain)
        {
            $repo = $this->entityManager->getRepository($this->getClass());
    
            return $repo->findOneBy(['domain' => $domain]);
        }
    
        /**
         * Returns the host are part of the host, from the related entity.
         *
         * @param Website $website
         * @return string
         */
        public function getHost($website): string
        {
            return $website->getDomain();
        }
}
```

Declare the service:

```yaml
# /config/services.yaml
services:
    App\Host\Service\WebsiteHostService:
        arguments:
            - "App\\Entity\\Website"
            - "@doctrine.orm.default_entity_manager"
        tags:
            - { name: loconox_entity_routing.host.service, alias: domain }
```