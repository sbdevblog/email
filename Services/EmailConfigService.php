<?php

namespace SbDevBlog\Email\Services;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class EmailConfigService
{
    private const ADMIN_CONTACT_RECIPIENT_EMAIL_XML_PATH = "contact/email/recipient_email";

    private const ADMIN_CONTACT_SENDER_EMAIL_XML_PATH = "contact/email/sender_email_identity";

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get Recipient EMail
     *
     * @return string
     */
    public function getRecipientEmail(): string
    {
        return $this->getConfig(self::ADMIN_CONTACT_RECIPIENT_EMAIL_XML_PATH);
    }

    /**
     * Get Configuration
     *
     * @param string $path
     * @return mixed
     */
    private function getConfig(string $path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Sender Details
     *
     * @return array
     */
    public function getSender(): array
    {
        return $this->getSenderDetails($this->getConfig(self::ADMIN_CONTACT_SENDER_EMAIL_XML_PATH));
    }

    /**
     * Get sender details
     *
     * @param string|null $senderType
     * @return array
     */
    private function getSenderDetails(string $senderType = null): array
    {
        $identity = $senderType ? "trans_email/ident_" . $senderType . "/" : "trans_email/ident_general/";
        return [
            "email" => $this->getConfig($identity . "email"),
            "name" => $this->getConfig($identity."name")
        ];
    }
}
