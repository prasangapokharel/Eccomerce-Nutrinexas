# PRODUCTION LEVEL PROJECT RULES

1. CODE STYLE  
   - Keep code clean, simple, and readable.  
   - Use clear names for variables, classes, and functions.  
   - Remove unused code, comments, test logs, and debug prints.  
   - Maintain proper indentation and spacing.  

2. FOLDER STRUCTURE  
   /app/config/config.php       → Main connection config file (use this only)  
   /database/                   → Store all database files  
   /database/migration/         → Keep all migration scripts here  
   /database/*.sql              → Exported database read file  
   /public/css/                 → Keep all CSS (no inline or internal CSS)  
   /public/js/                  → Keep all JS (no inline JS)  
   /test/controller/*.php       → For all test files  
   /app/                        → Core logic and controller files  

3. DATABASE & MIGRATION  
   - Always read the exported SQL file from `/database/` before starting tests.  
   - Use `/database/migration/` for schema updates or table changes.  
   - Use `/app/config/config.php` for all connections (do not duplicate).  
   - Always use maximum table fields while reading/writing data.  
   - Verify all fields match the latest migration file before deployment.  

4. TESTING  
   - Store all test scripts in `/test/controller/`.  
   - Run test cases after reading the latest database file.  
   - Ensure data is read correctly from the DB and no missing fields.  
   - Fix all test errors before pushing to production.  

5. PRODUCTION CHECKLIST  
   Step 1: Read database  
   Step 2: Run tests from `/test/controller/`  
   Step 3: Fix issues and confirm migration updates  
   Step 4: Clean up all unused code and config  
   Step 5: Deploy stable version  

6. SECURITY  
   - Never expose database credentials or API keys in public files.  
   - Use `.env` for sensitive values.  
   - Sanitize user input and validate all forms.  

7. PERFORMANCE  
   - Minify CSS, JS, and images before upload.  
   - Enable caching and compression.  
   - Avoid duplicate imports or libraries.  

8. DOCUMENTATION  
   - Add short comments for every function and class.  
   - Keep a `README.md` with setup and usage instructions.  
   - Add database relation details if needed.  

9. COMMIT RULES  
   - Commit only stable, tested files.  
   - Never push local configs, logs, or test data.  
   - Use short and clear commit messages.  

10. FINAL CHECK  
   - UI responsive and bug-free.  
   - All database fields used correctly.  
   - No internal CSS or JS.  
   - Code passes all tests.  
   - Ready for production push.
