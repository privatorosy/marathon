<?php
namespace Mediashare\Marathon\Service;

use Mediashare\Marathon\Collection\TaskCollection;
use Mediashare\Marathon\Entity\Config;
use Mediashare\Marathon\Entity\Task;
use Mediashare\Marathon\Exception\FileNotFoundException;
use Mediashare\Marathon\Exception\JsonDecodeException;
use Mediashare\Marathon\Exception\DurationStrToTimeException;
use Mediashare\Marathon\Exception\RemainingStrToTimeException;
use Mediashare\Marathon\Exception\TaskNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class TaskService {
    private Task|null $task = null;

    public function __construct(
        private readonly StepService $stepService,
        private readonly TimestampService $timestampService,
        private readonly SerializerService $serializerService,
        private readonly Filesystem $filesystem,
        private readonly ConfigService $configService,
    ) {}

    public function getConfigService(): ConfigService|null {
        return $this->configService;
    }

    public function setConfig(Config $config): self {
        $this->getConfigService()->setConfig($config);

        return $this;
    }

    public function getConfig(): Config {
        return $this->getConfigService()->getConfig();
    }

    /**
     * @throws JsonDecodeException
     * @throws FileNotFoundException
     */
    public function getTasks(): TaskCollection {
        $taskCollection = new TaskCollection();
        foreach (glob($this->getConfig()->getTaskDirectory() . DIRECTORY_SEPARATOR . '*.json') as $filepath):
            $taskCollection->add($this->serializerService->read($filepath, Task::class));
        endforeach;

        return $taskCollection;
    }

    /**
     * @throws TaskNotFoundException
     * @throws FileNotFoundException
     * @throws JsonDecodeException
     */
    public function getTask(bool $createItIfNotExist = false): Task {
        if ($this->task instanceof Task):
            return $this->task;
        endif;

        $filepath = $this->getTaskFilepath();
        $taskFileExist = $this->filesystem->exists($filepath);
        if ($taskFileExist):
            return $this
                ->setTask($this->serializerService->read($filepath, Task::class))
                ->getTask();
        elseif (($name = $this->getConfig()->getTaskId()) && $task = $this->findTaskByName($name)):
            $this->setConfig($this->getConfig()->setTaskId($task->getId()));
            return $task;
        elseif ($createItIfNotExist):
            return $this->create()->getTask();
        endif;

        throw new TaskNotFoundException($this->getConfig()->getTaskId());
    }

    private function findTaskByName(string $name): Task|null {
        if (($tasks = $this->getTasks()->findByName($name))->count() === 1):
            return $tasks->first();
        elseif ($tasks->count() > 1):
            throw new \Exception("Multiple tasks found with the same name"); 
        endif;

        return null;
    }

    public function setTask(Task|null $task = null): self {
        $this->task = $task;

        return $this;
    }

    public function create(array $data = []): self {
        /** @var Task $task */
        $task = $this->serializerService->arrayToEntity($data, Task::class);

        if (!$task->getId()):
            $task->setId($this->getConfig()->getTaskId() ?? (new \DateTime())->format('YmdHis'));
            $this->setConfig($this->getConfig()->setTaskId($task->getId()));
        endif;

        $this->setConfig($this->getConfig()->setTaskId($task->getId()));

        $this->serializerService->writeTask($this->getTaskFilepath(), $task);

        return $this->setTask($task);
    }

    /**
     * @throws FileNotFoundException
     * @throws JsonDecodeException
     * @throws DurationStrToTimeException
     * @throws TaskNotFoundException
     */
    public function start(bool|null $createItIfNotExist = true): self {
        $task = $this->getTask($createItIfNotExist)
            ->setRun(true)
            ->setArchived(false)
        ;

        if (!$task->getStartDate() || (!($lastStep = $task->getSteps()?->last()) || $lastStep->getSeconds() !== null)):
            $task
                ->addStep(
                    $this->stepService->create()
                );
        endif;
        
        return $this->setTask($task);
    }

    /**
     * @throws TaskNotFoundException
     * @throws JsonDecodeException
     * @throws FileNotFoundException
     */
    public function stop(bool $createItIfNotExist = false): self {
        $task = $this
            ->getTask($createItIfNotExist)
            ->setRun(false);
        
        if (($lastStep = $task->getSteps()?->last()) && $lastStep->getSeconds() === null):
            $task
                ->getSteps()
                ->offsetSet(
                    $task->getSteps()->getKey($lastStep),
                    $lastStep->setSeconds((new \DateTime())->getTimestamp() - $lastStep->getStartDate()),
                );
        endif;

        return $this->setTask($task);
    }

    /**
     * @throws FileNotFoundException
     * @throws JsonDecodeException
     * @throws TaskNotFoundException
     */
    public function archive(): self {
        $this
            ->stop()
            ->getTask()
            ->setArchived(true);

        return $this;
    }

    public function delete(): self {
        $this->filesystem->remove($this->getTaskFilepath());

        return $this->setTask(null);
    }

    public function getTaskFilepath(): string {
        return $this->getConfig()->getTaskDirectory().DIRECTORY_SEPARATOR.$this->sanitizeTaskIdForFilename().'.json';
    }

    private function sanitizeTaskIdForFilename(): string {
        $taskId = $this->getConfig()->getTaskId() ?? '';

        $sanitizedTaskId = preg_replace('/[<>:"\/\\|?*\x00-\x1F]+/u', '-', $taskId);
        $sanitizedTaskId = preg_replace('/[^\w.-]+/u', '-', $sanitizedTaskId ?? '');
        $sanitizedTaskId = trim($sanitizedTaskId, " .-\t\n\r\0\x0B");

        return $sanitizedTaskId !== '' && $sanitizedTaskId !== '.' && $sanitizedTaskId !== '..'
            ? $sanitizedTaskId
            : 'task';
    }

    /**
     * @throws DurationStrToTimeException
     * @throws RemainingStrToTimeException
     */
    public function update(
        string|false $name = false,
        string|false $duration = false,
        string|false $remaining = false,
    ): self {
        if ($name === false && $duration === false && $remaining === false):
            return $this;
        endif;

        $task = $this->getTask(createItIfNotExist: true);
        $task->setName($name !== false ? $name : $this->getTask()->getName());

        if ($duration !== false):
            $firstStep = $task->getSteps()->first();
            $task->getSteps()->clear();
            $task->addStep($this
                ->stepService
                ->createWithCustomDuration(
                    $duration,
                    $firstStep?->getStartDate(),
                )
            );
        endif;

        if ($remaining !== false):
            $remaining = $this->timestampService->convert($remaining);
            $timestamp = strtotime($remaining, $now = (new \DateTime())->getTimestamp());
            if (!$timestamp):
                throw new RemainingStrToTimeException($remaining);
            endif;
            $seconds = $timestamp - $now;
            $task->setRemaining($seconds);
        endif;

        return $this->setTask($task);
    }
}