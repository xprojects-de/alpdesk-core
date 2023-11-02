<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage\AwsS3;

use Alpdesk\AlpdeskCore\Library\Storage\GenericFlysystemStorage;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;

class AwsS3Storage extends GenericFlysystemStorage
{
    /**
     * @param array|null $config
     * @return void
     * @throws \Exception
     */
    public function initialize(?array $config): void
    {
        if (!\is_array($config) || \count($config) <= 0 || !isset($config['awss3'])) {
            throw new \Exception('invalid config');
        }

        if (
            !isset(
                $config['awss3']['key'],
                $config['awss3']['secret'],
                $config['awss3']['region'],
                $config['awss3']['bucket']
            )
        ) {
            throw new \Exception('invalid config');
        }

        $credentials = new Credentials((string)$config['awss3']['key'], (string)$config['awss3']['secret']);
        $client = new S3Client([
            'credentials' => $credentials,
            'region' => (string)$config['awss3']['region'],
            'version' => 'latest'
        ]);

        $this->filesystem = new Filesystem(new AwsS3V3Adapter($client, (string)$config['awss3']['bucket']));

        parent::initialize($config);

    }

}