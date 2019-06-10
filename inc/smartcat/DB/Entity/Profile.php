<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\DB\Entity;

class Profile
{
    /** * @var int */
    private $profileId;
    /** @var string */
    private $vendor;
    /** @var string */
    private $vendorName;
    /** @var string */
    private $sourceLanguage;
    /** @var string[] */
    private $targetLanguages;
    /** @var string[] */
    private $workflowStages;
    /** @var int */
    private $projectID;
    /** @var bool */
    private $autoSend;
    /** @var bool */
    private $autoUpdate;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->profileId;
    }

    /**
     * @param int $profileId
     */
    public function setId(int $profileId)
    {
        $this->profileId = $profileId;
    }

    /**
     * @return string
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * @param string $vendor
     */
    public function setVendor(string $vendor)
    {
        $this->vendor = $vendor;
    }

    /**
     * @return string
     */
    public function getVendorName(): string
    {
        return $this->vendorName;
    }

    /**
     * @param string $vendorName
     */
    public function setVendorName(string $vendorName)
    {
        $this->vendorName = $vendorName;
    }

    /**
     * @return string
     */
    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    /**
     * @param string $sourceLanguage
     */
    public function setSourceLanguage(string $sourceLanguage)
    {
        $this->sourceLanguage = $sourceLanguage;
    }

    /**
     * @return string[]
     */
    public function getTargetLanguages(): array
    {
        return $this->targetLanguages;
    }

    /**
     * @param string[] $targetLanguages
     */
    public function setTargetLanguages(array $targetLanguages)
    {
        $this->targetLanguages = $targetLanguages;
    }

    /**
     * @return string[]
     */
    public function getWorkflowStages(): array
    {
        return $this->workflowStages;
    }

    /**
     * @param string[] $workflowStages
     */
    public function setWorkflowStages(array $workflowStages)
    {
        $this->workflowStages = $workflowStages;
    }

    /**
     * @return bool
     */
    public function isAutoSend(): bool
    {
        return $this->autoSend;
    }

    /**
     * @param bool $autoSend
     */
    public function setAutoSend(bool $autoSend)
    {
        $this->autoSend = $autoSend;
    }

    /**
     * @return bool
     */
    public function isAutoUpdate(): bool
    {
        return $this->autoUpdate;
    }

    /**
     * @param bool $autoUpdate
     */
    public function setAutoUpdate(bool $autoUpdate)
    {
        $this->autoUpdate = $autoUpdate;
    }

    /**
     * @return int
     */
    public function getProjectID(): int
    {
        return $this->projectID;
    }

    /**
     * @param int $projectID
     */
    public function setProjectID(int $projectID)
    {
        $this->projectID = $projectID;
    }
}
