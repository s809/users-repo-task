<?php

require_once './vendor/autoload.php';

use Laminas\Diactoros\ServerRequest;
use MiladRahimi\PhpRouter\Exceptions\RouteNotFoundException;
use MiladRahimi\PhpRouter\Router;
use Laminas\Diactoros\Response\JsonResponse;


$router = Router::create();
$userRepository = new UserRepository();
$validation = new Validation();


// $router->get('/test', function () use ($userRepository) {
//     return new JsonResponse(['message' => $userRepository->test()]);
// });

// $router->get('/xdebug', function () use ($userRepository) {
//     xdebug_info();
//     xdebug_break();
//     exit;
// });

$router->post('/create', function (ServerRequest $request) use ($userRepository, $validation) {
    try {
        $json = $validation->getJsonData($request);

        $userData = $validation->filterAndValidateUserData($json, true);
    } catch (InvalidArgumentException $e) {
        return new JsonResponse([
            "success" => false,
            "result" => [
                "error" => $e->getMessage()
            ]
        ], status: 400);
    }

    return new JsonResponse([
        "success" => true,
        "result" => [
            "id" => $userRepository->create($userData)
        ]
    ], status: 201);
});

$router->get('/get', function (ServerRequest $request) use ($userRepository, $validation) {
    $queryParams = $request->getQueryParams();

    try {
        $userFields = $validation->filterUserFields($queryParams, false);
        $validation->validateUserFields($userFields, false);
    } catch (InvalidArgumentException $e) {
        return new JsonResponse([
            "success" => false,
            "result" => [
                "error" => $e->getMessage()
            ]
        ], status: 400);
    }

    $users = $userRepository->getByCriteria($userFields);
    if (!count($users)) {
        return new JsonResponse([
            "success" => false,
            "result" => [
                "error" => "No users found by this criteria"
            ]
        ], status: 404);
    }

    return new JsonResponse([
        "success" => true,
        "result" => [
            "users" => $users
        ]
    ]);
});

$router->get('/get/{id}', function ($id) use ($userRepository) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if ($id === false) {
        return new JsonResponse([
            "success" => false,
            "result" => [
                "error" => "Invalid user ID"
            ]
        ], status: 400);
    }

    $user = $userRepository->getById($id);
    if ($user === null) {
        return new JsonResponse([
            "success" => false,
            "result" => [
                "error" => "User with ID $id not found"
            ]
        ], status: 404);
    }

    return new JsonResponse([
        "success" => true,
        "result" => [
            "users" => [$user]
        ]
    ]);
});

$router->patch('/update/{id}', function (ServerRequest $request, $id) use ($userRepository, $validation) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if ($id === false) {
        return new JsonResponse([
            "success" => false,
            "result" => [
                "error" => "Invalid user ID"
            ]
        ], status: 400);
    }

    try {
        $json = $validation->getJsonData($request);

        $userData = $validation->filterAndValidateUserData($json, false);
    } catch (InvalidArgumentException $e) {
        return new JsonResponse([
            "success" => false,
            "result" => [
                "error" => $e->getMessage()
            ]
        ], status: 400);
    }

    $user = $userRepository->update($id, $userData);
    if ($user === null) {
        return new JsonResponse([
            "success" => false,
            "result" => [
                "error" => "User with ID $id not found"
            ]
        ], status: 404);
    }

    return new JsonResponse([
        "success" => true,
        "result" => $user
    ], status: 200);
});

$router->delete('/delete/{id}', function ($id) use ($userRepository) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if ($id === false) {
        return new JsonResponse([
            "success" => false,
            "result" => [
                "error" => "Invalid user ID"
            ]
        ], status: 400);
    }

    $user = $userRepository->deleteById($id);
    if ($user === null) {
        return new JsonResponse([
            "success" => false,
            "result" => [
                "error" => "User with ID $id not found"
            ]
        ], status: 404);
    }

    return new JsonResponse([
        "success" => true,
        "result" => [
            "users" => [$user]
        ]
    ]);
});

$router->delete('/delete', function () use ($userRepository) {
    $userRepository->deleteAll();
    return new JsonResponse([
        "success" => true
    ], status: 200);
});


try {
    $router->dispatch();
} catch (RouteNotFoundException $e) {
    $router->getPublisher()->publish(
        new JsonResponse([
            "success" => false,
            "result" => [
                "error" => "Not found"
            ]
        ], status: 404)
    );
}
