<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\QueryFactoryInterface.
 */

namespace Drupal\Core\Entity\Query;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines an interface for QueryFactory classes.
 */
interface QueryFactoryInterface {

  /**
   * Instantiates an entity query for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query for a specific configuration entity type.
   */
  public function get(EntityTypeInterface $entity_type, $conjunction);

  /**
   * Returns a aggregation query object for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @throws \Drupal\Core\Entity\Query\QueryException
   * @return \Drupal\Core\Entity\Query\QueryAggregateInterface
   *   The query object that can query the given entity type.
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction);

}
