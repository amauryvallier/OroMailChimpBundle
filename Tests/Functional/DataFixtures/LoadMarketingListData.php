<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListType;
use Oro\Bundle\TestFrameworkCRMBundle\Entity\TestCustomerWithContactInformation;

class LoadMarketingListData extends AbstractMailChimpFixture implements DependentFixtureInterface
{
    /**
     * @var array Channels configuration
     */
    protected $mlData = [
        [
            'type' => 'dynamic',
            'name' => 'Test ML',
            'description' => '',
            'entity' => Contact::class,
            'reference' => 'mailchimp:ml_one',
            'segment' => 'mailchimp:ml_one:segment',
        ],
        [
            'type' => 'dynamic',
            'name' => 'Test ML Customer',
            'description' => '',
            'entity' => TestCustomerWithContactInformation::class,
            'reference' => 'mailchimp:ml_two',
            'segment' => 'mailchimp:ml_two:segment',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->mlData as $data) {
            $entity = new MarketingList();
            $type = $manager
                ->getRepository(MarketingListType::class)
                ->find($data['type']);
            $segment = $this->getReference($data['segment']);
            $entity->setType($type);
            $entity->setSegment($segment);
            $this->setEntityPropertyValues($entity, $data, ['reference', 'type', 'segment']);
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentData::class,
            LoadContactData::class,
            LoadCustomerData::class,
        ];
    }
}
