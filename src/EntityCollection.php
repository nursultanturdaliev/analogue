<?php
namespace Analogue\ORM;

use Analogue\ORM\Mappable;
use Analogue\ORM\System\Manager;
use Illuminate\Support\Collection as Collection;

class EntityCollection extends Collection {
	
	/**
	 * Find an entity in the collection by key.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $default
	 * 
	 * @return \Analogue\ORM\Entity
	 */
	public function find($key, $default = null)
	{
		return array_first($this->items, function($itemKey, $entity) use ($key)
		{
			return $this->getEntityKey($entity) == $key;

		}, $default);
	}

	/**
	 * Add an entity to the collection.
	 *
	 * @param  Mappable  $entity
	 * @return $this
	 */
	public function add(Mappable $entity)
	{
		$this->items[] = $entity;

		return $this;
	}

	/**
	 * Remove an entity from the collection
	 */
	public function remove(Mappable $entity)
	{
		$keyName = $this->getEntityKey($entity);

		return $this->pull($entity->getEntityAttribute($keyName));
	}

	/**
	 * Determine if a key exists in the collection.
	 *
	 * @param  mixed  $key
	 * @return bool
	 */
	public function contains($key, $value = null)
	{
		return ! is_null($this->find($key));
	}

	/**
	 * Fetch a nested element of the collection.
	 *
	 * @param  string  $key
	 * @return static
	 */
	public function fetch($key)
	{
		return new static(array_fetch($this->toArray(), $key));
	}

	/**
	 * Get the max value of a given key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function max($key)
	{
		return $this->reduce(function($result, $item) use ($key)
		{
			return (is_null($result) || $item->getEntityAttribute($key) > $result) ? 
				$item->getEntityAttribute($key) : $result;
		});
	}

	/**
	 * Get the min value of a given key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function min($key)
	{
		return $this->reduce(function($result, $item) use ($key)
		{
			return (is_null($result) || $item->getEntityAttribute($key) < $result) 
				? $item->getEntityAttribute($key) : $result;
		});
	}

	/**
	 * Generic function for returning class.key value pairs
	 * 
	 * @return string
	 */
	public function getEntityHashes()
	{
		return array_map(function($entity) 
		{ 
			$class = get_class($entity);

			$mapper = Manager::mapper($class);
			
			$keyName = $mapper->getEntityMap()->getKeyName();
			
			return $class.'.'.$entity->getEntityAttribute($keyName); 
		}, 
		$this->items);
	}

	/**
	 * Get a subset of the collection from entity hashes
	 * 
	 * @param  array  $hashes 
	 * @return 
	 */
	public function getSubsetByHashes(array $hashes)
	{
		$subset = [];

		foreach($this->items as $item)
		{
			$class = get_class($item);

			$mapper = Manager::mapper($class);
			
			$keyName = $mapper->getEntityMap()->getKeyName();

			if(in_array($class.'.'.$item->$keyName, $hashes)) $subset[] = $item; 
		}

		return $subset;
	}

	/**
	 * Merge the collection with the given items.
	 *
	 * @param  \ArrayAccess|array  $items
	 * @return static
	 */
	public function merge($items)
	{
		$dictionary = $this->getDictionary();

		foreach ($items as $item)
		{
			$dictionary[$this->getEntityKey($item)] = $item;
		}

		return new static(array_values($dictionary));
	}

	/**
	 * Diff the collection with the given items.
	 *
	 * @param  \ArrayAccess|array  $items
	 * @return static
	 */
	public function diff($items)
	{
		$diff = new static;

		$dictionary = $this->getDictionary($items);

		foreach ($this->items as $item)
		{
			if ( ! isset($dictionary[$this->getEntityKey($item)]))
			{
				$diff->add($item);
			}
		}

		return $diff;
	}

	/**
	 * Intersect the collection with the given items.
	 *
 	 * @param  \ArrayAccess|array  $items
	 * @return static
	 */
	public function intersect($items)
	{
		$intersect = new static;

		$dictionary = $this->getDictionary($items);

		foreach ($this->items as $item)
		{
			if (isset($dictionary[$this->getEntityKey($item)]))
			{
				$intersect->add($item);
			}
		}

		return $intersect;
	}

	/**
	 * Return only unique items from the collection.
	 *
	 * @return static
	 */
	public function unique()
	{
		$dictionary = $this->getDictionary();

		return new static(array_values($dictionary));
	}

	/**
	 * Returns only the models from the collection with the specified keys.
	 *
	 * @param  mixed  $keys
	 * @return static
	 */
	public function only($keys)
	{
		$dictionary = array_only($this->getDictionary(), $keys);

		return new static(array_values($dictionary));
	}

	/**
	 * Returns all models in the collection except the models with specified keys.
	 *
	 * @param  mixed  $keys
	 * @return static
	 */
	public function except($keys)
	{
		$dictionary = array_except($this->getDictionary(), $keys);

		return new static(array_values($dictionary));
	}

	/**
	 * Get a dictionary keyed by primary keys.
	 *
	 * @param  \ArrayAccess|array  $items
	 * @return array
	 */
	public function getDictionary($items = null)
	{
		$items = is_null($items) ? $this->items : $items;

		$dictionary = array();

		foreach ($items as $value)
		{
			$dictionary[$this->getEntityKey($value)] = $value;
		}

		return $dictionary;
	}

	protected function getEntityKey(Mappable $entity)
	{
		$keyName = Manager::mapper($entity)->getEntityMap()->getKeyName();
		return $entity->getEntityAttribute($keyName);
	}

	/**
	 * Get a base Support collection instance from this collection.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function toBase()
	{
		return new BaseCollection($this->items);
	}
}