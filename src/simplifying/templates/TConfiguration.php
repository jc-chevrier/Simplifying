<?php

namespace simplifying\templates;

/**
 * Class TConfiguration.
 *
 * Cette classe est la classe des aspects configurables
 * dans le système de templates. Il s'agit d'une classe
 * pour la configuration de paramètres du système de templates.
 *
 * T <=> Template
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\templates
 */
class TConfiguration
{
    /**
     * @var callable $routeBuilder
     */
    public $routeBuilder;

    /**
     * @var TConfiguration $configuration;
     */
    private static $configuration;

    /**
     * TConfiguration constructor.
     */
    private function __construct() {}

    /**
     * @return TConfiguration
     */
    public static function getInstance() : TConfiguration {
        if(TConfiguration::$configuration == null) {
            TConfiguration::$configuration = new TConfiguration();
        }
        return TConfiguration::$configuration;
    }
}