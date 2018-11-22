<?php
	/*
	 *  Command Line (CLI) Application
	 */
	
use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;
use Phalcon\Loader;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;

	$di = new CliDI();

	$di->set(
		"pl",
		function () {
			return  require_once APP_PATH . '/plugins/ProjectLibraries/autoload.php';
		}
	);
	/**
	 * Database connection is created based in the parameters defined in the configuration file
	 */
	$di->setShared('db', function () {
		$config = $this->getConfig();

		$class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
		$params = [
			'host'     => $config->database->host,
			'username' => $config->database->username,
			'password' => $config->database->password,
			'dbname'   => $config->database->dbname,
			'charset'  => $config->database->charset
		];

		if ($config->database->adapter == 'Postgresql') {
			unset($params['charset']);
		}

		$connection = new $class($params);

		return $connection;
	});


	/**
	 * If the configuration specify the use of metadata adapter use it or use memory otherwise
	 */
	$di->setShared('modelsMetadata', function () {
		return new MetaDataAdapter();
	});


	/**
	 * Регистрируем автозагрузчик и сообщаем ему директорию
	 * для регистрации каталога задач
	 */
	$loader = new Loader();


	
	$configFile = __DIR__ . '/config/config.php';

	if (is_readable($configFile)) {
		$config = include $configFile;

		$di->set('config', $config);
	}

	$loader->registerDirs(
		[
			APP_PATH . '/tasks',
			$config->application->modelsDir,
			$config->application->controllersDir,
			$config->application->pluginsDir
		]
	);

	$loader->register();

	// Создание консольного приложения
	$console = new ConsoleApp();
	
	$console->setDI($di);

	/**
	 * Обработка аргументов консоли
	 */
	$arguments = [];

	foreach ($argv as $k => $arg) {
		if ($k === 1) {
			$arguments['task'] = $arg;
		} elseif ($k === 2) {
			$arguments['action'] = $arg;
		} elseif ($k >= 3) {
			$arguments['params'][] = $arg;
		}
	}

		
	try {
		// Обработка входящих аргументов
		$console->handle();
	} catch (\Phalcon\Exception $e) {
		// Связанные с Phalcon вещи указываем здесь
		fwrite(STDERR, $e->getMessage() . PHP_EOL);
		exit(1);
	} catch (\Throwable $throwable) {
		fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
		exit(1);
	} catch (\Exception $exception) {
		fwrite(STDERR, $exception->getMessage() . PHP_EOL);
		exit(1);
	}