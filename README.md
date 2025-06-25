<a id="readme-top"></a>

<br />
<div align="center">
  <a href="https://github.com/slimanimeddine/taskflow-be">
    <img src="public/logo.svg" alt="Logo" width="260" height="120">
  </a>

  <h3 align="center">TaskFlow Backend</h3>

  <p align="center">
    The Backend for TaskFlow, a project management platform.
    <br />
  </p>
</div>

<details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#about-the-project">About The Project</a>
      <ul>
        <li><a href="#other-parts">Other Parts</a></li>
        <li><a href="#features">Features</a></li>
        <li><a href="#built-with">Built With</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#prerequisites">Prerequisites</a></li>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li><a href="#usage">Usage</a></li>
    <li><a href="#license">License</a></li>
  </ol>
</details>

## About The Project

TaskFlow is a comprehensive project management tool designed to help teams organize tasks, track progress, and collaborate effectively.

### Other Parts

- [Frontend](https://github.com/slimanimeddine/taskflow-fe)

### Features

- User registration (sign-up) and login (sign-in)
- Email verification and password reset
- Secure authentication using Laravel Sanctum
- CRUD operations for workspaces, projects and tasks.
- Filter, Sort and paginate tasks.
- Documented endpoints using Scribe.

Lorem ipsum dolor sit amet,

<p align="right">(<a href="#readme-top">back to top</a>)</p>

### Built With

- [Laravel](https://laravel.com/)
- [Laravel-query-builder](https://spatie.be/docs/laravel-query-builder/v6/introduction)
- [Typesense](https://typesense.org/)
- [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum)
- [Model Typer](https://github.com/fumeapp/modeltyper)
- [Scribe](https://scribe.knuckles.wtf/)
- [Resend](https://resend.com/)

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Getting Started

### Prerequisites

Make sure you have [PHP](https://www.php.net/), [Composer](https://getcomposer.org/) installed on your local machine.
If you don't you can head to [Installing PHP and the Laravel Installer](https://laravel.com/docs/12.x#installing-php) to get your local machine setted up.

### Installation

Follow these instructions to run this project locally on your machine

1. Clone the repo:

   ```sh
   git clone https://github.com/slimanimeddine/taskflow-be.git
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

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Usage

The application will be available at `http://localhost:8000`.

The application's documentation will be available at `http://localhost:8000/docs`.

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## License

Distributed under the [MIT License](LICENSE.md).

<p align="right">(<a href="#readme-top">back to top</a>)</p>
