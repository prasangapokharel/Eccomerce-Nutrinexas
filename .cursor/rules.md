# Project: MVC PHP Application
# Expertise Level: Senior (12+ years)

## Code Quality Standards

1. **PSR Standards**
   - Follow PSR-12 coding standards
   - Use PSR-4 autoloading
   - Implement PSR-3 logging where applicable

2. **Naming Conventions**
   - Controllers: PascalCase with "Controller" suffix (BannerController)
   - Models: Singular PascalCase (Banner, User)
   - Services: PascalCase with "Service" suffix (BannerService)
   - Variables: camelCase ($userId, $bannerData)
   - Constants: UPPER_SNAKE_CASE (MAX_UPLOAD_SIZE)
   - Database tables: plural_snake_case (banner_ads, user_profiles)

3. **File Organization**
   - One class per file
   - File name matches class name
   - Namespace matches directory structure
   - Group related functionality in modules

4. **Security Rules**
   - ALWAYS escape output with htmlspecialchars() in views
   - ALWAYS use prepared statements for database queries
   - ALWAYS validate and sanitize user input
   - NEVER trust $_GET, $_POST, $_COOKIE directly
   - Implement CSRF protection for all forms
   - Use password_hash() for passwords, never md5() or sha1()

5. **Code Structure**
   - Maximum method length: 50 lines
   - Maximum class length: 300 lines
   - Maximum cyclomatic complexity: 10
   - Use early returns to reduce nesting
   - Avoid deep nesting (max 3 levels)

6. **Documentation**
   - PHPDoc for all public methods
   - Include @param, @return, @throws tags
   - Document complex business logic
   - Add file-level docblocks

7. **Error Handling**
   - Use try-catch blocks for risky operations
   - Create custom exception classes
   - Log all exceptions
   - Never expose system errors to users
   - Return user-friendly error messages

8. **Database**
   - Use repository pattern for data access
   - Never write SQL in controllers
   - Use query builder or ORM
   - Implement database transactions for multi-step operations
   - Index frequently queried columns

9. **Performance**
   - Implement caching where appropriate
   - Use lazy loading for relationships
   - Limit database queries in loops
   - Optimize image uploads
   - Use pagination for large datasets

10. **Testing**
    - Write unit tests for services
    - Mock dependencies in tests
    - Test edge cases and error conditions
    - Maintain >80% code coverage

## Code Generation Instructions

When generating code:
- Use dependency injection
- Implement interfaces for flexibility
- Follow SOLID principles
- Use type hints for PHP 7.4+
- Prefer composition over inheritance
- Keep methods focused (single responsibility)
- Return typed responses
- Include comprehensive error handling

## View Guidelines

- No SQL queries in views
- Minimal PHP logic (only loops and conditionals)
- Always escape output
- Use template inheritance
- Separate concerns (presentation vs logic)
- Use helpers for repetitive tasks

## Example Patterns to Follow

Controller → Service → Repository → Model
Request → Validation → Processing → Response
Input Sanitization → Business Logic → Data Persistence → Output

## Anti-Patterns to Avoid

- God classes (classes doing too much)
- Tight coupling between components
- Magic numbers (use named constants)
- Suppressing errors with @
- Using global variables
- Direct database access in controllers
- Business logic in views
- Inconsistent error handling

Example controller: Controllers/Ads/BannerController.php <?php

namespace App\Controllers\Ads;

use App\Controllers\BaseController;
use App\Services\Ads\BannerService;
use App\Validators\BannerValidator;

class BannerController extends BaseController
{
    private BannerService $bannerService;
    private BannerValidator $validator;

    public function __construct(BannerService $bannerService, BannerValidator $validator)
    {
        parent::__construct();
        $this->bannerService = $bannerService;
        $this->validator = $validator;
    }

    /**
     * Display listing of banners
     * 
     * @return void
     */
    public function index(): void
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            
            $banners = $this->bannerService->getPaginated($page, $limit);
            $totalCount = $this->bannerService->getTotalCount();
            
            $this->view('ads/banner/index', [
                'banners' => $banners,
                'pagination' => $this->buildPagination($page, $limit, $totalCount),
                'title' => 'Banner Management'
            ]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Show create form
     * 
     * @return void
     */
    public function create(): void
    {
        $this->view('ads/banner/create', [
            'title' => 'Create New Banner'
        ]);
    }

    /**
     * Store new banner
     * 
     * @return void
     */
    public function store(): void
    {
        try {
            // Validate input
            $data = $this->validator->validate($_POST, [
                'title' => 'required|max:255',
                'image' => 'required|image|max:2048',
                'link' => 'required|url',
                'status' => 'required|in:active,inactive'
            ]);

            // Process upload
            if (!empty($_FILES['image'])) {
                $data['image'] = $this->handleImageUpload($_FILES['image']);
            }

            $banner = $this->bannerService->create($data);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Banner created successfully',
                'data' => $banner
            ], 201);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update banner
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        try {
            $data = $this->validator->validate($_POST, [
                'title' => 'required|max:255',
                'link' => 'required|url',
                'status' => 'required|in:active,inactive'
            ]);

            $banner = $this->bannerService->update($id, $data);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Banner updated successfully',
                'data' => $banner
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete banner
     * 
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        try {
            $this->bannerService->delete($id);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Banner deleted successfully'
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}