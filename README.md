# Marathon
[![Marathon GitHub Release](https://img.shields.io/github/v/release/privatorosy/marathon.svg?style=flat)]()
[![Marathon GitHub pull requests](https://img.shields.io/github/issues-pr/privatorosy/marathon.svg?style=flat)]()

## Introduction
Marathon is a command-line tool written in PHP and Symfony that empowers you to efficiently **manage todo-list for your projects**. 
It provides a comprehensive solution for maintaining a project-related activities through commit history.
### Features
- **Commit:** Easily associate time entries with project commits to maintain a history of actions taken during the task.
- **Remaining time:** Organize your time into tasks with time remaining.
- **Emojies supports:** ( :pencil: `:pencil:`, :tada: `:tada:`, :beer: `:beer:` ect...) 5000+ emojies supported into commit message.
- **Markdown support:** Implementation of markdown synthax into commit message.
- **Custom tags supports:** (`<red>`, `<green-bg>`, `<bold>` ect...) Implemented into commit message.
## Installation
### Binary
```bash
curl --output marathon https://raw.githubusercontent.com/privatorosy/marathon/main/marathon
chmod 755 marathon
sudo cp marathon /usr/local/bin/marathon
marathon <command>
```
### Docker
#### Dockerfile
```bash
git clone https://github.com/privatorosy/marathon
cd marathon
docker build -t marathon .
docker run -it marathon <command>
```
### Composer
#### Basic
```bash
composer require privatorosy/marathon
./vendor/privatorosy/marathon/bin/marathon <command>
```
#### Global
```bash
composer global require privatorosy/marathon
marathon <command>
```
## Usage
Here are some examples of how to use Marathon:
- To check the time you spend on a project, you can create a task for each phase of the project.
- To check the time you spend on a recurring task, you can create a task with a start date and an end date.
- To check the time you spend on a task with a client or vendor, you can add this information to the task.

### Commands
```bash
  marathon task:list                        Displaying the tasks list
  marathon task:start <?task-id>            Starting step of task
  marathon task:stop <?task-id>             Stoping step of task
  marathon task:status <?task-id>           Displaying status of task
  marathon task:archive <task-id>           Archive the task by ID
  marathon task:delete <task-id>            Deleting the task by ID

  marathon commit:create <?commit-message>  Creating new commit into task
  marathon commit:edit <commit-id>          Editing the commit from task
  marathon commit:delete <commit-id>        Deleting the commit from task
  
  marathon git:gitignore                    Adding .marathon rule into .gitgnore
  marathon version:update <?version>        Update version of Marathon
```
### Task Workflow
#### Creating a task
```bash
marathon task:start # Start a task without specifying a name.
marathon task:start 123 # Start a task with the specified ID (e.g., ID 123).
marathon task:start --name "Feature Implementation" # Start a task and set the name to "Feature Implementation".
marathon task:start --new # Start a completely new task without specifying a name.
marathon task:start --duration 2h # Start a task and sets the duration of the current step to 2 hours.
marathon task:start --remaining 4h # Start a task and sets the remaining time of the task to 4 hours.
marathon task:start 456 --new --name "New feature" --duration 30min # Start a completely new task with the ID 456 and sets the duration of the current step to 30 minutes.
```
#### Creating a commit
```bash
marathon commit:create # Create a new commit without specifying a message.
marathon commit:create "Initial commit" # Create a new commit for the current task with the specified message.
marathon commit:create -E # Open default editor for write a message and commit.
marathon commit:create "This is a very long commit message describing the changes made in this commit. It covers multiple lines and provides detailed information about the updates." # Create a new commit with a long and detailed commit message.
marathon commit:create "Your commit message" --duration 1h # Create a new commit with a message and sets its duration to 1 hour.
marathon commit:create "Test update older task with ID 123 or create it" --task-id 123 # Create a new commit for the task specified by the ID.
marathon commit:create "Rollback" --duration "-1hour" # Create a new commit with a message and sets its duration to rollback (negative duration).
```
#### Editing a commit
```bash
marathon commit:edit <commit-id> --message "Updated message" --duration 30min # Edit the message and duration of a specific commit.
marathon commit:edit 456 -E # Open default editor for edit a message for commit ID 456.
marathon commit:edit 456 --duration 1h # Edit the commit with ID 456 and updates its duration to 1 hour.
marathon commit:edit 300 --task-id 123 --message "Update commit with ID 300 from task ID 123" # Edit the last commit from the task specified by the ID.
```
#### Deleting a commit
```bash
marathon commit:delete <commit-id> # Delete the commit with ID from the current task.
marathon commit:delete 456 # Delete the commit with ID 123 from the task specified by the ID 111 and save it into the configuration.
marathon commit:delete 300 --task-id 123 # Delete the commit with ID 300 from the task specified by the ID 123.
```
#### Displaying task status
```bash
marathon task:status # Display the status of the current task.
marathon task:status 123 # Display the status of the task with ID 123.
```
#### Stopping a task step
```bash
marathon task:stop # Stop the current step of the task with the default duration.
marathon task:stop <?task-id> # Stop the task step with task ID.
marathon task:stop 123 --duration 1h # Stop the task step with ID 123 and updates its duration to 1 hour.
```
#### Archive or deleting a task
```bash
marathon task:archive <task-id> # Archive the task with ID without stopping the current step.
marathon task:archive 123 # Archive the task with ID 123.

# or delete the task

marathon task:delete <task-id> # Delete the task with ID.
marathon task:delete 123 # Delete the task with ID 123.
```
Archive or delete the current task.
#### Displaying task list
```bash
marathon task:list # Display the list of tasks.
```
### Additional Commands
Adding Marathon rule to .gitignore
```bash
marathon git:gitignore # Add the .marathon rule to your project .gitignore file.
```
### Update Marathon to the latest version
```bash
marathon version:update # Update Marathon to the latest version.
```
### Configuration Options
Marathon provides several configuration options, edit the configuration file with the given parameters, that you can customize:

* `--config-path`: Specify the path to the JSON configuration file.
* `--config-datetime-format`: Set the DateTimeFormat for date format displayed.
* `--config-datetime-zone`: Set the DateTimeZone for UTC used.
* `--config-task-dir`: Set the directory path containing task files.
* `--config-editor`: Set the default editor command to use to write commits messages.

```bash
marathon task:start --config-path=/path/to/config/file --config-datetime-format="d/m/Y H:i:s" --config-datetime-zone="Europe/London" --config-task-dir=/path/to/tasks/directory --config-editor vim
```

Feel free to explore and make the most of Marathon to streamline your project management workflow!

## Contributing
Marathon is an open-source project. You can contribute to the project by submitting bug fixes, improvements, or new features.

To contribute to the project, you can follow these instructions:
- Clone the marathon GitHub repository
- Create a branch for your contribution
- Make your changes
- Test your changes with `bin/phpunit`
- Build your bin with `box compile`
- Submit a pull request

### Build a bin with Box
#### Box install
[Box2](https://github.com/box-project/box) used for binary generation from php project. **PHP >=8.1 is required.**
```bash
composer global require humbug/box
box
```
#### Box usage
```bash
composer install --no-scripts --no-autoloader
composer dump-autoload --optimize
box compile
```
## Conclusion
Marathon is a simple and effective tool that can help you better manage your time. If you are looking for a free and open-source time tracker, Marathon is a good option.
