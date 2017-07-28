<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\models;

use craft\base\Model;
use craft\helpers\StringHelper;
use flipbox\spark\helpers\ArrayHelper;
use flipbox\spark\helpers\SiteHelper;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Settings extends Model
{

    /**
     * The pending statuses
     */
    const STATUS_PENDING = 'pending';

    /**
     * Association key for user
     */
    const UNIQUE_ASSOCIATION_USER = 'user';

    /**
     * Association key for member
     */
    const UNIQUE_ASSOCIATION_MEMBER = 'member';

    /**
     * Custom statuses
     *
     * @var array
     */
    public $statuses;

    /**
     * An organization owner is required
     *
     * @var boolean
     */
    public $requireOwner = false;

    /**
     *  User can only be an owner of one organization
     *
     * @var boolean
     */
    public $uniqueOwner = true;

    /**
     * Restrict user associations to organizations
     *
     * @var string
     */
    private $uniqueAssociation = self::UNIQUE_ASSOCIATION_USER;

    /**
     * Enable public registration
     *
     * @var boolean
     */
    public $publicRegistration = true;

    /**
     * @var SiteSettings[]
     */
    private $sites = [];

    /**
     * @inheritdoc
     */
    public function attributes()
    {

        return array_merge(
            parent::attributes(),
            [
                'sites',
                'uniqueAssociation'
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [
                    [
                        'uniqueAssociation'
                    ],
                    'in',
                    'range' => [
                        self::UNIQUE_ASSOCIATION_USER,
                        self::UNIQUE_ASSOCIATION_MEMBER
                    ]
                ]
            ]
        );
    }

    /**
     * @return array
     */
    public function getStatuses()
    {

        if (is_null($this->statuses)) {
            return self::defaultStatuses();
        }

        return $this->statuses;
    }

    /**
     * Default array of statuses
     *
     * @return array
     */
    public static function defaultStatuses()
    {
        return [
            static::STATUS_PENDING => StringHelper::upperCaseFirst(self::STATUS_PENDING),
        ];
    }


    /**
     * @param int|null $siteId
     * @return SiteSettings
     */
    public function getSite(int $siteId = null)
    {

        $siteId = SiteHelper::resolveSiteId($siteId);

        if (!$settings = ArrayHelper::getValue($this->getSites(), $siteId)) {
            $settings = new SiteSettings([
                'siteId' => $siteId
            ]);

            $this->sites[$siteId] = $settings;
        }

        return $this->sites[$siteId];
    }

    /**
     * @param SiteSettings $settings
     * @return $this
     */
    public function addSite(SiteSettings $settings)
    {
        $this->sites[$settings->siteId] = $settings;
        return $this;
    }

    /**
     * Returns the unique user option
     *
     * @return string|null
     */
    public function getUniqueAssociation()
    {
        return $this->uniqueAssociation;
    }

    /**
     * Sets the unique user settings.
     *
     * @param string $uniqueAssociation
     *
     * @return $this
     */
    public function setUniqueAssociation(string $uniqueAssociation = null)
    {

        $this->uniqueAssociation = null;

        if (in_array(
            $uniqueAssociation,
            [
                self::UNIQUE_ASSOCIATION_USER,
                self::UNIQUE_ASSOCIATION_MEMBER,
            ],
            true
        )
        ) {
            $this->uniqueAssociation = $uniqueAssociation;
        }

        return $this;
    }

    /**
     * A user can be associated as a user to multiple organizations
     *
     * @return bool
     */
    public function hasAssociationRestriction()
    {
        return $this->getUniqueAssociation() !== null;
    }

    /**
     * A user can only be associated as a user to one organization
     *
     * @return bool
     */
    public function userAssociationRestriction()
    {
        return $this->getUniqueAssociation() === self::UNIQUE_ASSOCIATION_USER;
    }

    /**
     * A user can only be associated as a user or member to one organization
     *
     * @return bool
     */
    public function memberAssociationRestriction()
    {
        return $this->getUniqueAssociation() === self::UNIQUE_ASSOCIATION_MEMBER;
    }

    /**
     * Returns all of the settings.
     *
     * @return SiteSettings[]
     */
    public function getSites(): array
    {
        return $this->sites;
    }

    /**
     * Sets the type's site-specific settings.
     *
     * @param SiteSettings[] $siteSettings
     *
     * @return $this
     */
    public function setSites(array $siteSettings)
    {

        $this->sites = [];

        foreach ($siteSettings as $settings) {
            if (!$settings instanceof SiteSettings) {
                $settings = new SiteSettings($settings);
            }

            $this->addSite($settings);
        }

        return $this;
    }
}
