Usage
=====

Assuming you have a `Product` class:

```php
class Product
{
    private $name;
    \\ ...
}
```

Within you `ProductController`, create a route with the new annotation class `Loconox\EntityRoutingBundle\Annotation\Route`

```php
use Loconox\EntityRoutingBundle\Annotation\Route;
\\ ...

class ProductController extends Controller
{
    /**
     * @Route("/{product}", name="product")
     */
    public function productAction($product)
    {
        \\ ...
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
    app.slug.service.product:
        class: AppBundle\Slug\Service\ProductSlugService
        arguments:
            - "AppBundle\\Entity\\Product"
            - "@loconox_entity_routing.manager.slug"
        calls:
            - [ setEntityManager, [ "@loconox_entity_routing.entity_manager" ]]
        tags:
            - { name: loconox_entity_routing.slug.service, alias: product }
```
