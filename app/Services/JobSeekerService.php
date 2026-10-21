<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\JobSeekerRepository;
use App\Repositories\SkillRepository;
use App\Repositories\SchoolRepository;
use App\Repositories\UserLocationRepository;
use App\Repositories\UserRepository;
use Exception;

class JobSeekerService extends BaseService
{
    private JobSeekerRepository $jobseekerRepo;
    private SkillRepository $skillRepo;
    private SchoolRepository $schoolRepo;
    private UserLocationRepository $locationRepo;
    private UserRepository $userRepo;

    // Store warnings (non-blocking validation issues)
    private array $warnings = [];

    public function __construct(
        JobSeekerRepository $jobseekerRepo,
        SkillRepository $skillRepo,
        SchoolRepository $schoolRepo,
        UserLocationRepository $locationRepo,
        UserRepository $userRepo
    ) {
        parent::__construct();
        $this->jobseekerRepo = $jobseekerRepo;
        $this->skillRepo = $skillRepo;
        $this->schoolRepo = $schoolRepo;
        $this->locationRepo = $locationRepo;
        $this->userRepo = $userRepo;
    }

    // ==================== WIZARD PROFILE SUBMISSION ====================

    /**
     * Submit complete wizard profile
     * 
     * @param int $userId
     * @param array $wizardData
     * @return bool
     */
    public function submitWizardProfile(int $userId, array $wizardData): bool
    {
        $this->logInfo('Submitting wizard profile', ['user_id' => $userId]);
        $this->clearErrors();
        $this->clearWarnings();

        try {
            // 1. Validate all wizard data
            if (!$this->validateWizardData($wizardData)) {
                return false;
            }

            // 2. Prepare data (convert camelCase to snake_case)
            $preparedData = $this->prepareWizardData($wizardData);

            // 3. Save profile using repository
            $profileId = $this->saveProfileFromWizardData($userId, $preparedData);

            if (!$profileId) {
                throw new Exception('Failed to save profile');
            }

            $this->logInfo('Wizard profile submitted successfully', [
                'user_id' => $userId,
                'profile_id' => $profileId
            ]);

            return true;

        } catch (Exception $e) {
            $this->logError('Exception in submitWizardProfile', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('profile', 'An error occurred while submitting profile: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save profile from wizard data using repository methods
     * 
     * @param int $userId
     * @param array $data
     * @return int|null Profile ID
     */
    protected function saveProfileFromWizardData(int $userId, array $data): ?int
    {
        $loctaion = $this->locationRepo->getLatestLocation($userId);
        if ($loctaion) {
            $data['user_location_id'] = $loctaion['id'];
        }
        // Prepare profile data for repository
        $profileData = [
            'first_name' => $data['personal']['first_name'] ?? null,
            'last_name' => $data['personal']['last_name'] ?? null,
            'professional_title' => $data['personal']['professional_title'] ?? null,
            'summary' => $data['summary'] ?? null,
            'user_location_id' => $data['user_location_id'] ?? null,
            'years_experience' => $data['years_experience'] ?? null,
            'open_to_work' => $data['preferences']['open_to_work'] ?? true,
            'remote_preference' => $data['preferences']['remote'] ?? false,
            'relocation_willing' => $data['preferences']['relocation'] ?? false
        ];

        // Use repository's createOrUpdateProfile method
        $profileId = $this->jobseekerRepo->createOrUpdateProfile($userId, $profileData);

        if (!$profileId) {
            return null;
        }

        // Save skills
        if (!empty($data['skills'])) {
            $this->saveSkills($profileId, $data['skills']);
        }

        // Save education
        if (!empty($data['education'])) {
            $this->saveEducation($profileId, $data['education']);
        }

        // Save experience
        if (!empty($data['experience'])) {
            $this->saveExperience($profileId, $data['experience']);
        }

        // Update profile completion after all data is saved
        $completion = $this->jobseekerRepo->calculateCompletion($profileId);
        $this->jobseekerRepo->update($profileId, ['profile_completion' => $completion]);

        return $profileId;
    }

    public function getSettings(int $userId){
        $settings = $this->getProfile($userId);
    }

    
    // ==================== PROFILE RETRIEVAL ====================

    /**
     * Get complete jobseeker profile
     * 
     * @param int $userId
     * @return array|null
     */
    public function getProfile(int $userId): ?array
    {
        $this->logInfo('Fetching jobseeker profile', ['user_id' => $userId]);

        try {
            $profile = $this->jobseekerRepo->findByUser($userId);

            if (!$profile) {
                $this->logInfo('No profile found for user', ['user_id' => $userId]);
                return null;
            }

            $completeProfile = $this->jobseekerRepo->getCompleteProfile($profile['id']);

            if ($completeProfile) {
                // Add user data including avatar
                $user = $this->userRepo->findById($userId);
                $completeProfile['user'] = $user;
            }

            return $completeProfile;

        } catch (Exception $e) {
            $this->logError('Exception in getProfile', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    // ==================== VALIDATION METHODS ====================

    /**
     * Validate all wizard data
     * 
     * @param array $data
     * @return bool
     */
    protected function validateWizardData(array $data): bool
    {
        $this->clearErrors();

        // Validate personal info
        if (empty($data['personal']['firstName'])) {
            $this->addError('firstName', 'First name is required');
        }
        if (empty($data['personal']['lastName'])) {
            $this->addError('lastName', 'Last name is required');
        }
        if (empty($data['personal']['professionalTitle'])) {
            $this->addError('professionalTitle', 'Professional title is required');
        }

        // Validate summary
        if (empty($data['summary'])) {
            $this->addError('summary', 'Professional summary is required');
        } elseif (strlen($data['summary']) < 50) {
            $this->addWarning('summary', 'Consider adding more details to your summary (at least 50 characters)');
        }

        // Validate categories (interests) - optional but recommended
        if (empty($data['categories']) || count($data['categories']) < 1) {
            $this->addWarning('categories', 'Selecting interests helps personalize your experience');
        }

        // Validate education
        if (!empty($data['education'])) {
            if (!$this->validateEducation($data['education'])) {
                return false;
            }
        }

        // Validate experience
        if (!empty($data['experience'])) {
            if (!$this->validateExperience($data['experience'])) {
                return false;
            }
        }

        // Validate skills - optional but recommended
        if (!empty($data['skills'])) {
            if (!$this->validateSkills($data['skills'])) {
                return false;
            }
        } else {
            $this->addWarning('skills', 'Adding skills helps employers find you');
        }

        return empty($this->getErrors());
    }


    /**
     * Validate education entries
     * 
     * @param array $education
     * @return bool
     */
    protected function validateEducation(array $education): bool
    {
        $allowedLevels = ['high_school', 'certificate', 'diploma', 'bachelor', 'master', 'doctorate'];
        $currentYear = (int) date('Y');

        foreach ($education as $index => $edu) {
            $prefix = "education.{$index}";

            // Required fields
            if (empty($edu['school_id'])) {
                $this->addError("{$prefix}.school_id", 'School is required');
                return false;
            }

            if (empty($edu['education_level'])) {
                $this->addError("{$prefix}.education_level", 'Education level is required');
                return false;
            }

            if (empty($edu['start_year'])) {
                $this->addError("{$prefix}.start_year", 'Start year is required');
                return false;
            }

            // Validate education level
            if (!in_array($edu['education_level'], $allowedLevels)) {
                $this->addError("{$prefix}.education_level", 'Invalid education level');
                return false;
            }

            // Validate start year
            $startYear = (int) $edu['start_year'];
            if ($startYear < 1900 || $startYear > $currentYear + 1) {
                $this->addError("{$prefix}.start_year", 'Start year must be between 1900 and ' . ($currentYear + 1));
                return false;
            }

            $currentlyStudying = $edu['currently_studying'] ?? false;

            if ($currentlyStudying) {
                // Validate expected graduation year
                if (!empty($edu['expected_graduation_year'])) {
                    $expYear = (int) $edu['expected_graduation_year'];
                    if ($expYear <= $startYear) {
                        $this->addError("{$prefix}.expected_graduation_year", 'Expected graduation must be after start year');
                        return false;
                    }
                    if ($expYear > $currentYear + 10) {
                        $this->addWarning("{$prefix}.expected_graduation_year", 'Expected graduation year seems very far in the future');
                    }
                }
            } else {
                // Validate end year for completed education
                if (empty($edu['end_year'])) {
                    $this->addError("{$prefix}.end_year", 'End year is required when not currently studying');
                    return false;
                }

                $endYear = (int) $edu['end_year'];
                if ($endYear < $startYear) {
                    $this->addError("{$prefix}.end_year", 'End year cannot be before start year');
                    return false;
                }
                if ($endYear > $currentYear) {
                    $this->addError("{$prefix}.end_year", 'End year cannot be in the future');
                    return false;
                }

                // Warn about unusually long education duration
                $duration = $endYear - $startYear;
                if ($duration > 10) {
                    $this->addWarning("{$prefix}.duration", 'Education duration seems unusually long (' . $duration . ' years)');
                }
            }
        }

        return true;
    }

    /**
     * Validate experience entries
     * 
     * @param array $experience
     * @return bool
     */
    protected function validateExperience(array $experience): bool
    {
        $currentYear = (int) date('Y');

        foreach ($experience as $index => $exp) {
            $prefix = "experience.{$index}";

            if (empty($exp['company_name'])) {
                $this->addError("{$prefix}.company_name", 'Company name is required');
                return false;
            }

            if (empty($exp['role_title'])) {
                $this->addError("{$prefix}.role_title", 'Role title is required');
                return false;
            }

            if (empty($exp['start_year'])) {
                $this->addError("{$prefix}.start_year", 'Start year is required');
                return false;
            }

            $startYear = (int) $exp['start_year'];
            if ($startYear < 1900 || $startYear > $currentYear + 1) {
                $this->addError("{$prefix}.start_year", 'Start year must be between 1900 and ' . ($currentYear + 1));
                return false;
            }

            $currentlyWorking = $exp['currently_working'] ?? false;

            if (!$currentlyWorking) {
                if (empty($exp['end_year'])) {
                    $this->addError("{$prefix}.end_year", 'End year is required when not currently working');
                    return false;
                }

                $endYear = (int) $exp['end_year'];
                if ($endYear < $startYear) {
                    $this->addError("{$prefix}.end_year", 'End year cannot be before start year');
                    return false;
                }
                if ($endYear > $currentYear) {
                    $this->addError("{$prefix}.end_year", 'End year cannot be in the future');
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate skills entries
     * 
     * @param array $skills
     * @return bool
     */
    protected function validateSkills(array $skills): bool
    {
        $allowedProficiency = ['beginner', 'intermediate', 'advanced', 'expert'];

        foreach ($skills as $index => $skill) {
            $prefix = "skills.{$index}";

            if (empty($skill['name'])) {
                $this->addError("{$prefix}.name", 'Skill name is required');
                return false;
            }

            if (strlen($skill['name']) > 100) {
                $this->addError("{$prefix}.name", 'Skill name cannot exceed 100 characters');
                return false;
            }

            if (!empty($skill['proficiency']) && !in_array($skill['proficiency'], $allowedProficiency)) {
                $this->addError("{$prefix}.proficiency", 'Invalid proficiency level. Must be: beginner, intermediate, advanced, expert');
                return false;
            }

            if (!empty($skill['years']) && ((int) $skill['years'] < 0 || (int) $skill['years'] > 50)) {
                $this->addError("{$prefix}.years", 'Years of experience must be between 0 and 50');
                return false;
            }
        }

        return true;
    }

    // ==================== DATA PREPARATION ====================

    /**
     * Prepare wizard data (convert camelCase to snake_case)
     * 
     * @param array $data
     * @return array
     */
    protected function prepareWizardData(array $data): array
    {
        return [
            'personal' => [
                'first_name' => $data['personal']['firstName'] ?? '',
                'last_name' => $data['personal']['lastName'] ?? '',
                'professional_title' => $data['personal']['professionalTitle'] ?? ''
            ],
            'summary' => $data['summary'] ?? '',
            'years_experience' => isset($data['yearsExperience']) ? (int) $data['yearsExperience'] : null,
            'preferences' => [
                'remote' => $data['preferences']['remote'] ?? false,
                'relocation' => $data['preferences']['relocation'] ?? false,
                'open_to_work' => $data['preferences']['openToWork'] ?? true
            ],
            'skills' => array_map(function ($skill) {
                return [
                    'skill_id' => $skill['id'] ?? null,
                    'name' => trim($skill['name']),
                    'proficiency' => $skill['proficiency'] ?? 'intermediate',
                    'years' => isset($skill['years']) ? (int) $skill['years'] : null
                ];
            }, $data['skills'] ?? []),
            'education' => array_map(function ($edu) {
                return [
                    'school_id' => (int) $edu['school_id'],
                    'education_level' => $edu['education_level'],
                    'field_of_study' => $edu['field_of_study'] ?? null,
                    'grade' => $edu['grade'] ?? null,
                    'start_year' => (int) $edu['start_year'],
                    'end_year' => isset($edu['end_year']) ? (int) $edu['end_year'] : null,
                    'currently_studying' => $edu['currently_studying'] ?? false,
                    'expected_graduation_year' => isset($edu['expected_graduation_year']) ? (int) $edu['expected_graduation_year'] : null,
                    'description' => $edu['description'] ?? null
                ];
            }, $data['education'] ?? []),
            'experience' => array_map(function ($exp) {
                return [
                    'company_name' => $exp['company_name'],
                    'role_title' => $exp['role_title'],
                    'start_year' => (int) $exp['start_year'],
                    'end_year' => isset($exp['end_year']) ? (int) $exp['end_year'] : null,
                    'currently_working' => $exp['currently_working'] ?? false,
                    'description' => $exp['description'] ?? null
                ];
            }, $data['experience'] ?? [])
        ];
    }

    // ==================== SAVE METHODS ====================

    /**
     * Save skills using repository
     * 
     * @param int $profileId
     * @param array $skills
     * @return void
     */
    protected function saveSkills(int $profileId, array $skills): void
    {
        $skillData = [];

        foreach ($skills as $skill) {
            $skillId = $skill['skill_id'];

            // If no skill_id, try to find or create skill
            if (empty($skillId) && !empty($skill['name'])) {
                $existingSkill = $this->skillRepo->findByName($skill['name']);
                if ($existingSkill) {
                    $skillId = $existingSkill['id'];
                } else {
                    // Create new skill
                    $slug = $this->slugify($skill['name']);
                    $skillId = $this->skillRepo->create([
                        'name' => $skill['name'],
                        'slug' => $slug,
                        'category_id' => 1 // Default category
                    ]);
                }
            }

            if ($skillId) {
                $skillData[] = [
                    'skill_id' => $skillId,
                    'proficiency' => $skill['proficiency'],
                    'years_experience' => $skill['years']
                ];
            }
        }

        // Use repository's updateSkills method which handles transaction
        if (!empty($skillData)) {
            $this->jobseekerRepo->updateSkills($profileId, $skillData);
        }
    }

    /**
     * Save education using repository
     * 
     * @param int $profileId
     * @param array $education
     * @return void
     */
    protected function saveEducation(int $profileId, array $education): void
    {
        foreach ($education as $edu) {
            $this->jobseekerRepo->addEducation($profileId, $edu);
        }
    }

    /**
     * Save experience using repository
     * 
     * @param int $profileId
     * @param array $experience
     * @return void
     */
    protected function saveExperience(int $profileId, array $experience): void
    {
        foreach ($experience as $exp) {
            $this->jobseekerRepo->addExperience($profileId, $exp);
        }
    }

    // ==================== HELPER METHODS ====================

    /**
     * Generate slug from string
     * 
     * @param string $text
     * @return string
     */
    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return empty($text) ? 'n-a' : $text;
    }

    /**
     * Add warning (non-blocking validation issue)
     * 
     * @param string $field
     * @param string $message
     */
    protected function addWarning(string $field, string $message): void
    {
        $this->warnings[$field] = $message;
        $this->logWarning('Validation warning', ['field' => $field, 'message' => $message]);
    }

    /**
     * Clear warnings
     */
    protected function clearWarnings(): void
    {
        $this->warnings = [];
    }

    /**
     * Update profile photo
     * 
     * @param int $userId
     * @param string $photoPath
     * @return bool
     */
    public function updateProfilePhoto(int $userId, string $photoPath): bool
    {
        $this->logInfo('Updating profile photo', ['user_id' => $userId]);

        try {
            $userAvatarSaved = $this->userRepo->updateAvatar($userId, $photoPath);
            if (!$userAvatarSaved) {
                $this->addError('avatar', 'Avatar path was not saved to the user record.');
                $this->logError('User avatar database verification failed', [
                    'user_id' => $userId
                ]);
                return false;
            }

            $profileAvatarSaved = $this->jobseekerRepo->updateAvatarForUser($userId, $photoPath);

            if ($profileAvatarSaved) {
                $this->logInfo('Profile photo updated successfully', [
                    'user_id' => $userId
                ]);
            } else {
                $this->addError('avatar', 'Avatar path was saved to the user but not the jobseeker profile record.');
                $this->logError('Profile photo database verification failed', [
                    'user_id' => $userId
                ]);
            }

            return $profileAvatarSaved;

        } catch (Exception $e) {
            $this->logError('Exception in updateProfilePhoto', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            $this->addError('avatar', 'Could not save the avatar path to the profile.');
            return false;
        }
    }

    /**
     * Update CV
     * 
     * @param int $userId
     * @param string $cvPath
     * @return bool
     */
    public function updateCV(int $userId, string $cvPath): bool
    {
        $this->logInfo('Updating CV', ['user_id' => $userId]);

        try {
            $result = $this->jobseekerRepo->updateCV($userId, $cvPath);

            if ($result) {
                $this->logInfo('CV updated successfully', [
                    'user_id' => $userId
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('Exception in updateCV', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get CV path for user
     * 
     * @param int $userId
     * @return string|null
     */
    public function getCVPath(int $userId): ?string
    {
        $this->logInfo('Getting CV path', ['user_id' => $userId]);

        try {
            $profile = $this->jobseekerRepo->findByUser($userId);
            return $profile['resume_url'] ?? null;

        } catch (Exception $e) {
            $this->logError('Exception in getCVPath', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete CV
     * 
     * @param int $userId
     * @return bool
     */
    public function deleteCV(int $userId): bool
    {
        $this->logInfo('Deleting CV', ['user_id' => $userId]);

        try {
            $result = $this->jobseekerRepo->deleteCV($userId);

            if ($result) {
                $this->logInfo('CV deleted successfully', [
                    'user_id' => $userId
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('Exception in deleteCV', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get warnings
     * 
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Update user preferences
     * 
     * @param int $userId
     * @param array $preferences
     * @return bool
     */
    public function updatePreferences(int $userId, array $preferences): bool
    {
        try {
            $profile = $this->jobseekerRepo->findByUser($userId);
            if (!$profile) {
                $this->addError('profile', 'Profile not found');
                return false;
            }

            $updateData = [
                'open_to_work' => isset($preferences['openToWork']) ? (bool) $preferences['openToWork'] : $profile['open_to_work'],
                'remote_preference' => isset($preferences['remote']) ? (bool) $preferences['remote'] : $profile['remote_preference'],
                'relocation_willing' => isset($preferences['relocation']) ? (bool) $preferences['relocation'] : $profile['relocation_willing'],
            ];

            return $this->jobseekerRepo->update($profile['id'], $updateData);

        } catch (\Exception $e) {
            $this->logError('Exception in updatePreferences', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->addError('profile', 'An error occurred while updating preferences');
            return false;
        }
    }

    /**
     * Update career information
     * 
     * @param int $userId
     * @param array $careerData
     * @return bool
     */
    public function updateCareer(int $userId, array $careerData): bool
    {
        try {
            $profile = $this->jobseekerRepo->findByUser($userId);
            if (!$profile) {
                $this->addError('profile', 'Profile not found');
                return false;
            }

            $updateData = [];
            
            if (isset($careerData['yearsExperience'])) {
                $updateData['years_experience'] = !empty($careerData['yearsExperience']) ? (int) $careerData['yearsExperience'] : null;
            }

            if (!empty($updateData)) {
                return $this->jobseekerRepo->update($profile['id'], $updateData);
            }

            return true;

        } catch (\Exception $e) {
            $this->logError('Exception in updateCareer', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->addError('profile', 'An error occurred while updating career information');
            return false;
        }
    }

    /**
     * Update profile basic information
     * 
     * @param int $userId
     * @param array $profileData
     * @return bool
     */
    public function updateProfile(int $userId, array $profileData): bool
    {
        try {
            $profile = $this->jobseekerRepo->findByUser($userId);
            if (!$profile) {
                $this->addError('profile', 'Profile not found');
                return false;
            }

            $personal = $profileData['personal'] ?? [];
            $summary = $profileData['summary'] ?? '';

            $updateData = [
                'first_name' => trim($personal['firstName'] ?? ''),
                'last_name' => trim($personal['lastName'] ?? ''),
                'professional_title' => trim($personal['professionalTitle'] ?? ''),
                'summary' => trim($summary),
            ];

            // Filter out empty strings
            $updateData = array_filter($updateData, fn($v) => $v !== '');

            if (empty($updateData)) {
                return true;
            }

            return $this->jobseekerRepo->update($profile['id'], $updateData);

        } catch (\Exception $e) {
            $this->logError('Exception in updateProfile', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->addError('profile', 'An error occurred while updating profile');
            return false;
        }
    }
}