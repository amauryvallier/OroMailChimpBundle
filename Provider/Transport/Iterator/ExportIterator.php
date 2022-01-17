<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use GuzzleHttp\Psr7\Utils;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * Mailchimp API export calls queue iterator.
 */
class ExportIterator implements \Iterator
{
    /**
     * @var MailChimpClient
     */
    protected $client;

    /**
     * @var StreamInterface
     */
    protected $bodyStream;

    /**
     * @var string
     */
    protected $methodName;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var array|null
     */
    protected $header;

    /**
     * @var mixed
     */
    protected $current = null;

    /**
     * @var mixed
     */
    protected $offset = -1;

    /**
     * @var bool
     */
    protected $useFirstLineAsHeader;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param MailChimpClient $client
     * @param string $methodName
     * @param array $parameters
     * @param bool $useFirstLineAsHeader
     */
    public function __construct(
        MailChimpClient $client,
        $methodName,
        array $parameters = [],
        $useFirstLineAsHeader = true
    ) {
        $this->client = $client;
        $this->methodName = $methodName;
        $this->parameters = $parameters;
        $this->useFirstLineAsHeader = $useFirstLineAsHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->current = $this->read();
        if ($this->valid()) {
            $this->offset += 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        return $this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->current !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->offset = -1;
        $this->current = null;
        $this->bodyStream = null;
        $this->header = null;

        $this->next();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Read one line from export response, if read success converts line to associative array according to export data
     * format.
     *
     * @return array|null
     */
    protected function read()
    {
        if (!$this->bodyStream) {
            $response = $this->client->export($this->methodName, $this->parameters);
            $this->bodyStream = $response->getBody();

            $this->bodyStream->seek(0, \SEEK_SET);

            if ($this->useFirstLineAsHeader) {
                $line = $this->getLineData();
                if ($line) {
                    $this->header = $line;
                }
            }
        }

        return $this->getResponseItem();
    }

    /**
     * @return array|null
     */
    protected function getLineData()
    {
        $line = Utils::readLine($this->bodyStream);
        return json_decode($line, JSON_OBJECT_AS_ARRAY);
    }

    /**
     * @return array|null
     */
    protected function getResponseItem()
    {
        $line = $this->getLineData();
        if (!$line) {
            return null;
        }

        if ($this->useFirstLineAsHeader) {
            if (count($this->header) !== count($line)) {
                if ($this->logger) {
                    $this->logger->info(sprintf(
                        'Notice: The line is skipped, ' .
                        'as the number of elements for the header and the line is not the same. ' .
                        'Header count: "%s", line count: "%s", ' .
                        'header: "%s", line: "%s"',
                        count($this->header),
                        count($line),
                        json_encode($this->header),
                        json_encode($line)
                    ));
                }

                return null;
            }
            $line = array_combine($this->header, $line);
        }

        return $line;
    }
}
