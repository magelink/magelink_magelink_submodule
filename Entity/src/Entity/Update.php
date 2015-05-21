<?php
/**
 * Represents an instance of a Magelink Entity Update.
 * @category Entity
 * @package Entity
 * @author Matt Johnston
 * @author Andreas Gerhards <andreas@lero9.co.nz>
 * @copyright Copyright (c) 2014 LERO9 Ltd.
 * @license Commercial - All Rights Reserved
 */

namespace Entity;

use Entity\Entity;
use Magelink\Exception\MagelinkException;


class Update
{
    const TYPE_CREATE = 0;
    const TYPE_UPDATE = 1;
    const TYPE_DELETE = 2;
    const TYPE_ACTION = 9;

    /** @var int $log_id */
    protected $log_id;

    /** @var Entity $entity */
    protected $entity;

    /** @var string $type */
    protected $type;

    /** @var int $timestamp */
    protected $timestamp;

    /** @var int $source_node */
    protected $source_node;

    /** @var array $affected_nodes */
    protected $affected_nodes;

    /** @var array $affected_attributes */
    protected $affected_attributes;


    /**
     * @param \Entity\Entity $entity
     * @param array $data
     */
    public function init(Entity $entity, array $data)
    {
        $dataKeys = array('log_id', 'type', 'timestamp', 'source_node', 'affected_nodes', 'affected_attributes');
        $this->entity = $entity;
        foreach ($dataKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new MagelinkException('Could not find value of '.$key.' for update '.$data['update_id']);
                break;
            }else {
                if (strpos($key, 'affected_') === 0 && !is_array($data[$key])) {
                    $data[$key] = explode(',', $data[$key]);
                }

                $this->$key = $data[$key];
            }
        }
    }

    /**
     * @return int $this->log_id
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * @return Entity $this->entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string $this->type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int $this->timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return int $this->source_node
     */
    public function getSourceNode()
    {
        return $this->source_node;
    }

    /**
     * @return int[] $this->affectedNodes
     */
    public function getNodesSimple()
    {
        return $this->affected_nodes;
    }

    /**
     * @return string[]
     */
    public function getAttributesSimple()
    {
        return $this->affected_attributes;
    }
    
}