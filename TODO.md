# TODO: Fix Issues and Improve Manga Status Site

## Security Fixes
- [x] Fix SQL Injection in home.php pagination query
- [x] Add CSRF protection to login and register forms
- [x] Improve session security in auth.php
- [x] Secure database credentials in db.php (environment variables added)
- [x] Review and fix any remaining SQL injection vulnerabilities in other files (list_users.php, change.php now use prepared statements)

## Code Quality Improvements
- [x] Add proper error handling in db.php (connection errors and charset errors added)
- [ ] Refactor inline PHP in home.php for better readability
- [x] Add input validation and sanitization where needed (added to login and register forms)
- [x] Improve code comments and documentation (added comprehensive comments to db.php and auth.php)
- [x] Optimize database queries and add indexes if needed (created database_optimizations.sql with recommended indexes)

## Performance Enhancements
- [ ] Implement caching for frequently accessed data
- [ ] Optimize JavaScript loading and execution
- [x] Add lazy loading for images (already implemented in home.php and browse.php)
- [ ] Minimize CSS and JS files

## User Experience Improvements
- [ ] Add loading indicators for AJAX requests
- [ ] Improve responsive design
- [ ] Add accessibility features (ARIA labels, keyboard navigation)
- [ ] Enhance error messages and user feedback

## Testing and Validation
- [ ] Test all forms for security vulnerabilities
- [ ] Validate database connections and queries
- [ ] Test pagination and search functionality
- [ ] Cross-browser compatibility testing
