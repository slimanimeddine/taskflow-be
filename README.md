<a id="readme-top"></a>

<br />
<div align="center">
  <h3 align="center">Laravel Api Starter</h3>

  <p align="center">
    A starter code for an api only laravel project
    <br />
  </p>
</div>

## About The Project

This is a starter kit for api only laravel projects.

## Getting Started

### Prerequisites

Make sure you have [PHP](https://www.php.net/), [Composer](https://getcomposer.org/) installed on your local machine.
If you don't you can head to [Installing PHP and the Laravel Installer](https://laravel.com/docs/11.x#installing-php) to get your local machine setted up.

### Installation

Follow these instructions to run this project locally on your machine

1. Clone the repo:

   ```sh
   git clone https://github.com/slimanimeddine/arthive-backend.git
   ```

2. Copy `.env.example` to `.env`

3. Create a database and copy its credentials to `.env`

4. Install the project's dependencies:

   ```sh
   composer install
   ```

5. Generate the application key:

   ```sh
   php artisan key:generate
   ```

6. Run database migrations:

   ```sh
   php artisan migrate
   ```

7. Create a symbolic link to the storage folder:

   ```sh
   php artisan storage:link
   ```

8. Start the Laravel development server:

   ```sh
   php artisan serve
   ```

9. I have used [Resend](https://resend.com/) as an email API to send emails such as email verificaiton codes and forgot password codes.
   To configure Resend with Laravel follow these [instructions](https://resend.com/docs/send-with-laravel).

## Usage

The application will be available at `http://localhost:8000`.

The application's documentation will be available at `http://localhost:8000/docs`.

## License

Distributed under the [MIT License](LICENSE.md).
