<?php

namespace crawler\parser\interface;

/**
 * ParserProvisioningInterface defines methods preparing and providing (provisioning) data to the ParserController.
 */
interface ParserProvisionerInterface
{
    /**
     * Lists all sources for parser index action.
     * @return array
     */
    public static function listSources();

    /**
     * Lists all source categories for parser trial action.
     * @param integer $sourceId
     * @return array
     */
    public static function listSourceCategories(int $sourceId);

    /**
     * Lists all source keywords for parser trial action.
     * @param integer $sourceId
     * @return array
     */
    public static function listSourceKeywords(int $sourceId);

}