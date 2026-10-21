<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\DTOs\TestDTO;
use App\DTOs\JobSeekerProfileDTO;
use PDO;

class TestController extends BaseController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        parent::__construct();
        $this->pdo = $pdo;
    }

    public function index()
    {
        $this->view('test/index', [
            'title' => 'Test Page'
        ]);
    }

    public function hello($params)
    {
        $name = $params['name'] ?? 'Stranger';

        $this->view('test/hello', [
            'title' => 'Hello Page',
            'name' => $name
        ]);
    }

    /**
     * Test DTO with POST data
     */
    public function testDto()
    {
        // Get JSON input
        $data = $this->getJsonInput();

        // Create DTO from request
        $dto = TestDTO::fromRequest($data);

        // Validate
        $errors = $dto->validate();

        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'errors' => $errors
            ], 422);
        }

        // DTO is valid - use it
        return $this->json([
            'success' => true,
            'message' => 'DTO test successful',
            'data' => $dto->toArray(),
            'full_name' => $dto->name,
            'age_valid' => $dto->age > 0
        ]);
    }

    /**
     * Test jobseeker profile DTO
     */
    public function testJobSeekerDto()
    {
        $data = $this->getJsonInput();

        $dto = JobSeekerProfileDTO::fromRequest($data);
        $errors = $dto->validate();

        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'errors' => $errors
            ], 422);
        }

        return $this->json([
            'success' => true,
            'message' => 'JobSeeker DTO valid',
            'data' => $dto->toArray()
        ]);
    }

    public function apiTest()
    {
        $this->json([
            'success' => true,
            'message' => 'API is working',
            'time' => date('Y-m-d H:i:s')
        ]);
    }
}
