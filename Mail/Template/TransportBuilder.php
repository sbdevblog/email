<?php

namespace SbDevBlog\Email\Mail\Template;

use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\MimeInterface;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Laminas\Mime\Mime;
use Laminas\Mime\PartFactory;

/**
 * TransportBuilder for Mail Templates
 */
class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * Param that used for storing all message data until it will be used
     *
     * @var array
     */
    private array $messageData = [];

    /**
     * @var EmailMessageInterfaceFactory
     */
    private EmailMessageInterfaceFactory $emailMessageInterfaceFactory;

    /**
     * @var MimeMessageInterfaceFactory
     */
    private MimeMessageInterfaceFactory $mimeMessageInterfaceFactory;

    /**
     * @var MimePartInterfaceFactory
     */
    private MimePartInterfaceFactory $mimePartInterfaceFactory;

    /**
     * @var PartFactory
     */
    private PartFactory $partFactory;

    /**
     * @var array
     */
    private array $attachments = [];

    /**
     * Constructor
     *
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param PartFactory $partFactory
     * @param MessageInterfaceFactory|null $messageFactory
     * @param EmailMessageInterfaceFactory|null $emailMessageInterfaceFactory
     * @param MimeMessageInterfaceFactory|null $mimeMessageInterfaceFactory
     * @param MimePartInterfaceFactory|null $mimePartInterfaceFactory
     * @param AddressConverter|null $addressConverter
     */
    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        PartFactory $partFactory,
        MessageInterfaceFactory $messageFactory = null,
        EmailMessageInterfaceFactory $emailMessageInterfaceFactory = null,
        MimeMessageInterfaceFactory $mimeMessageInterfaceFactory = null,
        MimePartInterfaceFactory $mimePartInterfaceFactory = null,
        AddressConverter $addressConverter = null
    ) {
        $this->partFactory = $partFactory;
        $this->emailMessageInterfaceFactory = $emailMessageInterfaceFactory ?: $this->objectManager
            ->get(EmailMessageInterfaceFactory::class);
        $this->mimeMessageInterfaceFactory = $mimeMessageInterfaceFactory ?: $this->objectManager
            ->get(MimeMessageInterfaceFactory::class);
        $this->mimePartInterfaceFactory = $mimePartInterfaceFactory ?: $this->objectManager
            ->get(MimePartInterfaceFactory::class);

        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory,
            $messageFactory,
            $emailMessageInterfaceFactory,
            $mimeMessageInterfaceFactory,
            $mimePartInterfaceFactory,
            $addressConverter
        );
    }

    /**
     * Prepare message.
     *
     * @return $this
     * @throws LocalizedException if template type is unknown
     */
    protected function prepareMessage()
    {
        $template = $this->getTemplate();
        $content = $template->processTemplate();

        switch ($template->getType()) {
            case TemplateTypesInterface::TYPE_TEXT:
                $partType = MimeInterface::TYPE_TEXT;
                break;

            case TemplateTypesInterface::TYPE_HTML:
                $partType = MimeInterface::TYPE_HTML;
                break;

            default:
                throw new LocalizedException(
                    new Phrase('Unknown template type')
                );
        }

        /** @var \Magento\Framework\Mail\MimePartInterface $mimePart */
        $mimePart = $this->mimePartInterfaceFactory->create(
            [
                'content' => $content,
                'type' => $partType
            ]
        );
        $this->messageData['encoding'] = $mimePart->getCharset();
        $parts = count($this->attachments) ? array_merge([$mimePart], $this->attachments) : [$mimePart];

        $this->messageData['body'] = $this->mimeMessageInterfaceFactory->create(
            ['parts' =>$parts]
        );

        // phpcs:disable Magento2.Functions.DiscouragedFunction
        $this->messageData['subject'] = html_entity_decode(
            (string)$template->getSubject(),
            ENT_QUOTES
        );

        $this->message = $this->emailMessageInterfaceFactory->create($this->messageData);

        return $this;
    }
    /**
     * Adding Attachments to the email
     *
     * @param mixed $content
     * @param string $fileName
     * @param string $fileType
     * @return $this
     */
    public function addAttachment($content, $fileName, $fileType)
    {
        $attachmentPart = $this->partFactory->create();
        $attachmentPart->setContent($content)
            ->setType($fileType)
            ->setFileName($fileName)
            ->setDisposition(Mime::DISPOSITION_ATTACHMENT)
            ->setEncoding(Mime::ENCODING_BASE64);
        $this->attachments[] = $attachmentPart;

        return $this;
    }
}
