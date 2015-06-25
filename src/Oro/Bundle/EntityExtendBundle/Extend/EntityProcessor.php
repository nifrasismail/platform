<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Psr\Log\LoggerInterface;

use Oro\Bundle\InstallerBundle\Process\PhpExecutableFinder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\PlatformBundle\Maintenance\Mode as MaintenanceMode;

class EntityProcessor
{
    /** @var MaintenanceMode */
    protected $maintenance;

    /** @var ConfigManager */
    protected $configManager;

    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string */
    protected $cacheDir;

    /**
     * @var array
     *
     * Disable sync caches for doctrine related commands
     * because in other case entity classes and Doctrine metadata do not match each other
     * and as result DoctrineDataCollector raises an exception
     */
    protected $commands = [
        'oro:entity-extend:update-config' => ['--disable-cache-sync' => true],
        'oro:entity-extend:update-schema' => ['--disable-cache-sync' => true]
    ];

    /**
     * @var array
     */
    protected $finalizeCommands = [
        'router:cache:clear'  => [],
        'fos:js-routing:dump' => ['--target' => 'web/js/routes.js']
    ];

    /**
     * @param MaintenanceMode $maintenance
     * @param ConfigManager   $configManager
     * @param CommandExecutor $commandExecutor
     * @param LoggerInterface $logger
     * @param string          $cacheDir
     */
    public function __construct(
        MaintenanceMode $maintenance,
        ConfigManager $configManager,
        CommandExecutor $commandExecutor,
        LoggerInterface $logger,
        $cacheDir
    ) {
        $this->maintenance     = $maintenance;
        $this->configManager   = $configManager;
        $this->commandExecutor = $commandExecutor;
        $this->logger          = $logger;
        $this->cacheDir        = $cacheDir;
    }

    /**
     * Update database and generate extended field
     *
     * @param bool $generateProxies
     * @return bool
     */
    public function updateDatabase($generateProxies = true)
    {
        set_time_limit(0);

        $this->maintenance->activate();

        $isSuccess = $this->executeCommand($this->commands);

        if ($isSuccess && $generateProxies) {
            $this->generateProxies();
        }

        if ($isSuccess) {
            $isSuccess = $this->executeCommand($this->finalizeCommands);
        }

        return $isSuccess;
    }

    /**
     * Generate doctrine proxy classes for extended entities
     *
     * @param string|null $cacheDir
     */
    public function generateProxies($cacheDir = null)
    {
        $em = $this->configManager->getEntityManager();

        $isAutoGenerated = $em->getConfiguration()->getAutoGenerateProxyClasses();
        if (!$isAutoGenerated) {
            $proxyDir = $em->getConfiguration()->getProxyDir();
            if (!empty($cacheDir) && $this->cacheDir !== $cacheDir && strpos($proxyDir, $this->cacheDir) === 0) {
                $proxyDir = $cacheDir . substr($proxyDir, strlen($this->cacheDir));
            }
            $extendConfigProvider = $this->configManager->getProvider('extend');
            $extendConfigs        = $extendConfigProvider->getConfigs(null, true);
            foreach ($extendConfigs as $extendConfig) {
                if (!$extendConfig->is('is_extend')) {
                    continue;
                }
                if ($extendConfig->in('state', [ExtendScope::STATE_NEW])) {
                    continue;
                }

                $entityClass   = $extendConfig->getId()->getClassName();
                $proxyFileName = $proxyDir . DIRECTORY_SEPARATOR . '__CG__'
                    . str_replace('\\', '', $entityClass) . '.php';
                if (!file_exists($proxyFileName)) {
                    $proxyFactory = $em->getProxyFactory();
                    $metadata     = $em->getClassMetadata($entityClass);

                    $proxyFactory->generateProxyClasses([$metadata], $proxyDir);
                    clearstatcache(true, $proxyFileName);
                }
            }
        }
    }

    /**
     * @param array $commands
     *
     * @return bool
     */
    protected function executeCommand(array $commands)
    {
        $exitCode = 0;
        foreach ($commands as $command => $options) {
            $code = $this->commandExecutor->runCommand(
                $command,
                $options,
                $this->logger
            );

            if ($code !== 0) {
                $exitCode = $code;
            }
        }

        return $exitCode === 0;
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    protected function getPhp()
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find()) {
            throw new \RuntimeException(
                'The php executable could not be found, add it to your PATH environment variable and try again'
            );
        }

        return $phpPath;
    }
}
