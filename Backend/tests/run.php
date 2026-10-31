<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use BioVet\App\Application;
use BioVet\App\Http\Controllers\AuthController;
use BioVet\App\Http\Controllers\HealthController;
use BioVet\App\Http\Controllers\TokenVerificationController;
use BioVet\App\Http\Controllers\UserProfileController;
use BioVet\App\Http\HttpRequest;
use BioVet\App\Models\User;
use BioVet\App\Repositories\DatabaseHealthRepository;
use BioVet\App\Repositories\UserRepository;
use BioVet\App\Security\TokenService;
use BioVet\App\Services\AuthService;
use BioVet\App\Services\HealthService;
use BioVet\App\Services\TokenVerificationService;
use BioVet\App\Services\UserProfileService;

final class FakeUserRepository implements UserRepository
{
    public array $users = [];
    public array $created = [];
    public array $updatedLastLogin = [];
    public bool $duplicateOnCreate = false;

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
                return $user;
            }
        }

        return null;
    }

    public function findById(int|string $id): ?User
    {
        foreach ($this->users as $user) {
            if ((string) $user->id === (string) $id) {
                return $user;
            }
        }

        return null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash,
        string $role,
        ?string $phone
    ): int {
        if ($this->duplicateOnCreate) {
            $exception = new PDOException('Duplicate');
            $exception->errorInfo = ['23000', 1062, 'duplicate'];
            throw $exception;
        }

        $this->created = compact('name', 'email', 'passwordHash', 'role', 'phone');
        return 42;
    }

    public function updateLastLogin(int|string $id): void
    {
        $this->updatedLastLogin[] = $id;
    }
}

final class FakeTokenService implements TokenService
{
    public ?array $payload = null;
    public array $issuedClaims = [];

    public function issue(array $claims): string
    {
        $this->issuedClaims = $claims;
        return 'test.jwt.token';
    }

    public function verify(string $token): ?array
    {
        return $token === 'test.jwt.token' ? $this->payload : null;
    }
}

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function decodeResponse(\BioVet\App\Http\HttpResponse $response): array
{
    $decoded = json_decode($response->body, true);
    expect(is_array($decoded), 'Expected a JSON object response.');
    return $decoded;
}

$assertions = 0;
$users = new FakeUserRepository();
$tokens = new FakeTokenService();
$passwordHash = password_hash('correct horse battery', PASSWORD_DEFAULT);
$user = new User(7, 'Ana Vet', 'ana@example.test', 'veterinario', '555-0100', $passwordHash, '2026-10-25 12:00:00', '2026-01-01 09:00:00');
$users->users = [$user];
$auth = new AuthService($users, $tokens);

$login = $auth->login('ana@example.test', 'correct horse battery');
expect($login['type'] === 'success', 'Valid login should succeed.');
expect($tokens->issuedClaims === [
    'user_id' => 7,
    'email' => 'ana@example.test',
    'rol' => 'veterinario'
], 'Login should issue the established JWT claims.');
expect($users->updatedLastLogin === [7], 'Successful login should update last access.');
$assertions += 13;

expect($auth->login('ana@example.test', 'wrong password')['type'] === 'invalid_credentials', 'Wrong password should be rejected.');
expect($auth->login('missing@example.test', 'password')['type'] === 'invalid_credentials', 'Unknown email should be rejected.');
$assertions += 2;

$invalidRegistration = $auth->register((object) [
    'name' => '',
    'email' => 'not-an-email',
    'password' => 'short',
    'password_confirmation' => 'different'
]);
expect($invalidRegistration['type'] === 'validation_error', 'Invalid registration should return validation errors.');
expect(isset($invalidRegistration['errors']['name'], $invalidRegistration['errors']['email'], $invalidRegistration['errors']['password_confirmation']), 'Registration should retain field-specific errors.');
$assertions += 2;

$newRegistration = $auth->register((object) [
    'name' => '  Dr. New  ',
    'email' => 'new@example.test',
    'password' => 'password-123',
    'password_confirmation' => 'password-123',
    'telefono' => ' 555-0199 ',
    'rol' => 'admin'
]);
expect($newRegistration['type'] === 'created' && $newRegistration['user'] === [
    'id' => 42,
    'name' => 'Dr. New',
    'email' => 'new@example.test'
], 'Successful registration should preserve response fields and trimmed values.');
expect($users->created['role'] === 'veterinario' && $users->created['phone'] === '555-0199', 'Registration should force the default role and retain optional phone.');
$assertions += 2;

$users->duplicateOnCreate = true;
$raceDuplicate = $auth->register((object) [
    'name' => 'Dr. Race',
    'email' => 'race@example.test',
    'password' => 'password-123',
    'password_confirmation' => 'password-123'
]);
expect($raceDuplicate['type'] === 'duplicate_email', 'A unique-index race should map to the duplicate-email outcome.');
$users->duplicateOnCreate = false;
$assertions++;

$authController = new AuthController($auth);
$badLoginResponse = $authController->login(new HttpRequest('POST', [], [], '{"email":3}'));
expect($badLoginResponse->status === 400 && decodeResponse($badLoginResponse) === [
    'success' => false,
    'mensaje' => 'Email y contraseña son requeridos'
], 'Login input errors should retain their HTTP contract.');
$assertions++;

$successfulLoginResponse = $authController->login(new HttpRequest(
    'POST',
    [],
    [],
    '{"email":"ana@example.test","password":"correct horse battery"}'
));
$successfulLoginBody = decodeResponse($successfulLoginResponse);
expect($successfulLoginResponse->status === 200
    && array_keys($successfulLoginBody) === ['success', 'mensaje', 'token', 'usuario']
    && $successfulLoginBody['usuario'] === $user->loginRepresentation(),
    'Successful login should preserve its exact response keys and user fields.');
$assertions++;

$registerUsers = new FakeUserRepository();
$registerController = new AuthController(new AuthService($registerUsers, $tokens));
$successfulRegistration = $registerController->register(new HttpRequest(
    'POST',
    [],
    [],
    '{"name":"New Vet","email":"new-controller@example.test","password":"password-123","password_confirmation":"password-123"}'
));
$successfulRegistrationBody = decodeResponse($successfulRegistration);
expect($successfulRegistration->status === 201
    && array_keys($successfulRegistrationBody) === ['success', 'mensaje', 'usuario']
    && $successfulRegistrationBody['usuario'] === [
        'id' => 42,
        'name' => 'New Vet',
        'email' => 'new-controller@example.test'
    ],
    'Successful registration should preserve its exact status and response shape.');
$assertions++;

$largeBodyResponse = $registerController->register(new HttpRequest('POST', [], [], str_repeat('x', 65537)));
expect($largeBodyResponse->status === 413, 'Registration body limit should remain 65536 bytes.');
$assertions++;

$profileTokens = new FakeTokenService();
$profileTokens->payload = ['user_id' => 7, 'exp' => time() + 1200, 'iat' => time() - 5];
$profileController = new UserProfileController(new UserProfileService($users), $profileTokens);
$profileResponse = $profileController->show(new HttpRequest('GET', ['Authorization' => 'Bearer test.jwt.token'], [], ''));
$profileBody = decodeResponse($profileResponse);
expect($profileResponse->status === 200 && $profileResponse->headers['Cache-Control'] === 'no-store', 'Profile should succeed without caching.');
expect($profileBody['usuario'] === [
    'id' => 7,
    'name' => 'Ana Vet',
    'email' => 'ana@example.test',
    'rol' => 'veterinario',
    'telefono' => '555-0100',
    'ultimo_acceso' => '2026-10-25 12:00:00',
    'fecha_registro' => '2026-01-01 09:00:00'
], 'Profile contract should include the existing user fields and registration date.');
$assertions += 2;

$missingToken = $profileController->show(new HttpRequest('GET', [], [], ''));
expect($missingToken->status === 401 && decodeResponse($missingToken)['error'] === 'MISSING_TOKEN', 'Missing profile token should remain a 401.');
$assertions++;

$verificationTokens = new FakeTokenService();
$verificationTokens->payload = ['user_id' => 7, 'exp' => time() + 1200, 'iat' => time() - 5];
$verificationController = new TokenVerificationController(
    new TokenVerificationService($users, $verificationTokens)
);
$verificationResponse = $verificationController->verify(
    new HttpRequest('GET', [], ['token' => 'test.jwt.token'], '')
);
$verificationBody = decodeResponse($verificationResponse);
expect($verificationResponse->status === 200 && $verificationBody['valid'] === true, 'Query-token verification should retain its success contract.');
expect(array_keys($verificationBody) === ['success', 'valid', 'mensaje', 'usuario', 'token'], 'Verification must retain its distinct response shape.');
$assertions += 2;

$preflight = $verificationController->verify(new HttpRequest('OPTIONS', [], [], ''));
expect($preflight->status === 200 && $preflight->body === '', 'PHP verification OPTIONS must remain an empty 200 response.');
$assertions++;

$application = new Application(
    $authController,
    $verificationController,
    $profileController,
    new HealthController(new HealthService(new DatabaseHealthRepository(static fn () => null)))
);
$registerRoute = loadBioVetRouteRegistry()['api']['/api/register'];
$rejectedMethod = $application->dispatch($registerRoute, new HttpRequest('GET', [], [], ''));
expect($rejectedMethod->status === 405 && $rejectedMethod->headers['Allow'] === 'POST', 'PHP route method policy should retain registration Allow header.');
$assertions++;

$registry = loadBioVetRouteRegistry();
expect(($registry['pages']['/inicio']['php'] ?? true) === false, '/inicio should remain Express-only.');
expect(array_key_exists('php_methods', $registry['api']['/api/verify-token'])
    && $registry['api']['/api/verify-token']['php_methods'] === null, 'PHP verify-token should retain its permissive method policy.');
foreach ([
    '/assets/home.css',
    '/assets/login.css',
    '/assets/home.js',
    '/assets/session.js',
    '/assets/login.js',
    '/assets/register.js',
    '/assets/api-client.js',
    '/assets/session-store.js',
    '/assets/images/fondo.jpg',
    '/assets/images/logo.jpg',
    '/assets/images/rio.jpg'
] as $assetPath) {
    expect(isset($registry['assets'][$assetPath]), "Missing shared asset route {$assetPath}.");
}
$assertions += 3;

fwrite(STDOUT, "PHP MVC unit tests passed ({$assertions} assertions).\n");
