<?php

namespace SbDevBlog\Email\Services;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Translate\Inline\StateInterface;
use SbDevBlog\Email\Mail\Template\TransportBuilder;
use Magento\Store\Model\Store;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\Driver\File as Driver;
use Psr\Log\LoggerInterface;

class EmailService
{
    /**
     * @var EmailConfigService
     */
    private EmailConfigService $emailConfigService;

    /**
     * @var StateInterface
     */
    private StateInterface $state;

    /**
     * @var TransportBuilder
     */
    private TransportBuilder $transportBuilder;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var File
     */
    private File $file;

    /**
     * @var Driver
     */
    private Driver $driver;

    /**
     * Constructor
     *
     * @param EmailConfigService $emailConfigService
     * @param StateInterface $state
     * @param TransportBuilder $transportBuilder
     * @param File $file
     * @param Driver $driver
     * @param LoggerInterface $logger
     */
    public function __construct(
        EmailConfigService $emailConfigService,
        StateInterface     $state,
        TransportBuilder   $transportBuilder,
        File $file,
        Driver $driver,
        LoggerInterface    $logger
    ) {
        $this->emailConfigService = $emailConfigService;
        $this->state = $state;
        $this->transportBuilder = $transportBuilder;
        $this->file = $file;
        $this->driver = $driver;
        $this->logger = $logger;
    }

    /**
     * Send Email
     *
     * @param mixed $templateId
     * @param string|null $recipient
     * @param array $sender
     * @param array $emailVariables
     * @param array $ccEmail
     * @param array $bccEmail
     * @param array $attachments
     * @return bool
     */
    public function sendEmail(
        $templateId,
        string $recipient = null,
        array $sender = [],
        array $emailVariables = [],
        array $ccEmail = [],
        array $bccEmail = [],
        array $attachments = []
    ): bool {
        $this->state->suspend();
        $recipient = $recipient ? $recipient : $this->emailConfigService->getRecipientEmail();
        $sender = !empty($sender) ? $sender : $this->emailConfigService->getSender();
        try {
            $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars($emailVariables)
                ->setFromByScope($sender)
                ->addTo($recipient);

            foreach ($attachments as $attachment) {
                $fileInfo = $this->file->getPathInfo($attachment);
                $this->transportBuilder->addAttachment(
                    $this->driver->fileGetContents($attachment),
                    $fileInfo['basename'],
                    mime_content_type($attachment)
                );
            }

            if (!empty($ccEmail)) {
                $this->transportBuilder->addCc($ccEmail);
            }

            if (!empty($bccEmail)) {
                $this->transportBuilder->addBcc($bccEmail);
            }
            $transport = $this->transportBuilder->getTransport();

            $transport->sendMessage();
            $this->state->resume();
            return true;
        } catch (MailException|LocalizedException|\Exception $e) {
            $this->logger->info($e->getMessage());
        }
        $this->state->resume();
        return false;
    }
}
