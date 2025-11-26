# Contributing to Shield

Thank you for considering contributing to Shield! We appreciate your help in making this package better for the Laravel community.

## How to Contribute

### Reporting Bugs

Before creating a bug report, please check [existing issues](https://github.com/NahidFerdous/shield/issues) to avoid duplicates.

When reporting a bug, include:

-   Clear description of the issue
-   Steps to reproduce
-   Expected vs actual behavior
-   Environment details (PHP version, Laravel version, Shield version, OS)

### Suggesting Features

We welcome feature suggestions! Please:

-   Use a clear and descriptive title
-   Explain why this feature would be useful
-   Provide examples of how it would work

### Pull Requests

1. Fork the repository and create your branch from `main`
2. Make your changes following our coding standards
3. Add tests for new functionality
4. Ensure all tests pass with `composer test`
5. Update documentation if needed
6. Submit your pull request with a clear description

## Development Setup

### Prerequisites

-   PHP 8.2 or higher
-   Composer
-   Laravel 12.0 or higher

### Getting Started

1. Clone your fork:

    ```bash
    git clone https://github.com/YOUR_USERNAME/shield.git
    cd shield
    ```

2. Install dependencies:

    ```bash
    composer install
    ```

3. Run tests:
    ```bash
    composer test
    ```

### Testing

Shield uses Pest for testing. Run the test suite with:

```bash
composer test
```

## Coding Standards

-   Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
-   Use type hints for parameters and return types
-   Add `declare(strict_types=1);` to all PHP files
-   Use meaningful variable and method names
-   Keep methods focused and avoid deep nesting
-   Follow Laravel naming conventions

## Writing Tests

-   Write tests for all new features and bug fixes
-   Use descriptive test names
-   Follow Pest's testing style
-   Test both success and failure scenarios

Example:

```php
it('creates a user with default role', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->hasRole('user'))
        ->toBeTrue();
});
```

## Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) format:

```
<type>: <description>

[optional body]

[optional footer]
```

**Types:**

-   `feat`: New feature
-   `fix`: Bug fix
-   `docs`: Documentation changes
-   `refactor`: Code refactoring
-   `test`: Test changes
-   `chore`: Maintenance tasks

**Examples:**

```
feat: add shield:export-roles command
fix: prevent suspended users from logging in
docs: update installation instructions
```

## Documentation

-   Update README.md for new features
-   Add PHPDoc blocks to all public methods
-   Include usage examples where helpful
-   Keep documentation clear and concise

## Getting Help

-   Check the [README](README.md) for documentation
-   Search [existing issues](https://github.com/NahidFerdous/shield/issues)
-   Open a [discussion](https://github.com/NahidFerdous/shield/discussions)

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).

---

Thank you for contributing to Shield! 🚀
