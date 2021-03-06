<?php
/**
 * A transform to copy data from a foreign key referenced Entity to this Entity.
 *   Example: Copy custom product attribute on to order item:
 *     src_attribute = product, dest_attribute=custname, foreign_type=product, foreign_att=custname
 * @package Router\Transform
 * @author Matt Johnston
 * @author Andreas Gerhards <andreas@lero9.co.nz>
 * @copyright Copyright (c) 2014 LERO9 Ltd.
 * @license http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause - Please view LICENSE.md for more information
 */

namespace Router\Transform;


class DenormalizeTransform extends AbstractTransform
{

    /** @var \Entity\Entity $this->_foreignEntity  The foreign entity (resolved) */
    protected $_foreignEntity;
    /** @var string $this->_foreignAttribute  The attribute to pull from the foreign entity */
    protected $_foreignAttribute;


    /**
     * Perform any initialization/setup actions, and check any prerequisites.
     * @return boolean $isTransformEligible  Whether this transform is eligible to run
     */
    protected function _init()
    {
        if (!$this->getDestAttribute()) {
            // Ensure destination attribute configured
            return FALSE;
        }

        $entityType = $this->_transformEntity->getSimpleData('foreign_type');
        $this->_foreignAttribute = $this->_transformEntity->getSimpleData('foreign_att');

        if (!$entityType || !$this->_foreignAttribute) {
            return FALSE; // Insufficient data
        }

        $entityType = $this->_entityConfigService->parseEntityType($entityType);
        if (!$entityType) {
            return FALSE; // Bad entity type
        }

        $fkeyAttribute = $this->getSourceAttribute();

        $this->_foreignEntity = $this->_entity->resolve($fkeyAttribute['code'], $entityType);
        if($this->_foreignEntity === null){
            return TRUE; // We know result will be null, no need to check further.
        }

        if(!$this->_foreignEntity->hasAttribute($this->_foreignAttribute)){
            // Ensure we have the attribute we need in the new entity
            $this->_entityService->enhanceEntity(FALSE, $this->_foreignEntity, array($this->_foreignAttribute));
        }

        return TRUE;
    }

    /**
     * Apply the transform on any necessary data
     * @return array $newCodeValuePair  New data changes to be merged into the update.
     */
    public function _apply()
    {
        $dest = $this->getDestAttribute();

        $data = null;
        if($this->_foreignEntity !== null){
            $data = $this->_foreignEntity->getData($this->_foreignAttribute);
        }

        return array($dest['code']=>$data);
    }

}
