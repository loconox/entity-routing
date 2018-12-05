<?php

namespace Loconox\EntityRoutingBundle\Slug\Service;

use Loconox\EntityRoutingBundle\Model\SlugInterface;
use Loconox\EntityRoutingBundle\Service\ServiceInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

interface SlugServiceInterface extends ServiceInterface
{
    /**
     * Get the entity linked to the slug
     *
     * @param SlugInterface $slug
     * @return mixed
     */
    public function getEntity(SlugInterface $slug);

    /**
     * Get the slug linked to the entity
     *
     * @param $entity
     * @param bool $create
     * @param bool $optional
     * @return SlugInterface|false
     */
    public function findSlug($entity, $create = false, $optional = false);

    /**
     * Create a Slug linked to the entity
     *
     * @param mixed $entity
     * @return SlugInterface
     */
    public function createSlug($entity);

    /**
     * Update the link with the linked entity
     *
     * @param mixed $entity
     * @return SlugInterface the updated Slug
     */
    public function updateSlug($entity);

    /**
     * Create a new slug linked to the updated entity
     *
     * @param $entity
     * @param SlugInterface $oldSlug
     * @return SlugInterface the new slug
     */
    public function incrementSlug($entity, SlugInterface $oldSlug);

    /**
     * Set the slug of the entity
     *
     * @param $entity
     * @param SlugInterface $slug
     */
    public function setEntitySlug(SlugInterface $slug, $entity);

    /**
     * Get the slug linked to the entity
     *
     * @param mixed $entity
     * @return string
     */
    public function getEntitySlug($entity);

    /**
     * Initialize slug values
     *
     * @param SlugInterface $slug
     * @param $entity
     * @return mixed
     */
    public function setValues(SlugInterface $slug, $entity);

    /**
     * Returns true if the entity has changed, false otherwise
     *
     * @param $entity
     * @return bool
     */
    public function hasChanged($entity);

    /**
     * Returns the entity id
     *
     * @param $entity
     * @return mixed
     */
    public function getEntityId($entity);

    /**
     * Validates the entity. Use it before building the slug.
     *
     * @param $value
     *
     * @return ConstraintViolationListInterface A list of constraint violations
     *                                          If the list is empty, validation
     *                                          succeeded
     */
    public function validate($value);
}