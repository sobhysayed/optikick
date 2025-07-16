#!/bin/bash

# Optikick Test Runner Script
# This script provides easy commands to run different types of tests

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to show help
show_help() {
    echo "Optikick Test Runner"
    echo ""
    echo "Usage: $0 [COMMAND] [OPTIONS]"
    echo ""
    echo "Commands:"
    echo "  unit                    Run all unit tests"
    echo "  feature                 Run all feature tests"
    echo "  all                     Run all tests"
    echo "  coverage                Run tests with coverage report"
    echo "  parallel                Run tests in parallel"
    echo "  specific <file>         Run specific test file"
    echo "  method <method>         Run specific test method"
    echo "  watch                   Run tests in watch mode"
    echo "  performance             Run performance tests"
    echo "  help                    Show this help message"
    echo ""
    echo "Options:"
    echo "  --verbose               Show verbose output"
    echo "  --stop-on-failure       Stop on first failure"
    echo "  --coverage-html         Generate HTML coverage report"
    echo "  --coverage-text         Generate text coverage report"
    echo ""
    echo "Examples:"
    echo "  $0 unit"
    echo "  $0 unit --verbose"
    echo "  $0 specific tests/Unit/UserTest.php"
    echo "  $0 method test_can_create_user"
    echo "  $0 coverage --coverage-html"
}

# Function to check if Laravel is available
check_laravel() {
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed or not in PATH"
        exit 1
    fi

    if [ ! -f "artisan" ]; then
        print_error "Laravel artisan file not found. Make sure you're in the project root."
        exit 1
    fi
}

# Function to run unit tests
run_unit_tests() {
    print_status "Running unit tests..."
    
    local args="--testsuite=Unit"
    
    if [[ "$*" == *"--verbose"* ]]; then
        args="$args --verbose"
    fi
    
    if [[ "$*" == *"--stop-on-failure"* ]]; then
        args="$args --stop-on-failure"
    fi
    
    php artisan test $args
    
    if [ $? -eq 0 ]; then
        print_success "Unit tests completed successfully!"
    else
        print_error "Unit tests failed!"
        exit 1
    fi
}

# Function to run feature tests
run_feature_tests() {
    print_status "Running feature tests..."
    
    local args="--testsuite=Feature"
    
    if [[ "$*" == *"--verbose"* ]]; then
        args="$args --verbose"
    fi
    
    if [[ "$*" == *"--stop-on-failure"* ]]; then
        args="$args --stop-on-failure"
    fi
    
    php artisan test $args
    
    if [ $? -eq 0 ]; then
        print_success "Feature tests completed successfully!"
    else
        print_error "Feature tests failed!"
        exit 1
    fi
}

# Function to run all tests
run_all_tests() {
    print_status "Running all tests..."
    
    local args=""
    
    if [[ "$*" == *"--verbose"* ]]; then
        args="$args --verbose"
    fi
    
    if [[ "$*" == *"--stop-on-failure"* ]]; then
        args="$args --stop-on-failure"
    fi
    
    php artisan test $args
    
    if [ $? -eq 0 ]; then
        print_success "All tests completed successfully!"
    else
        print_error "Some tests failed!"
        exit 1
    fi
}

# Function to run tests with coverage
run_coverage_tests() {
    print_status "Running tests with coverage..."
    
    local args="--coverage"
    
    if [[ "$*" == *"--coverage-html"* ]]; then
        args="$args --coverage-html=coverage/html"
        print_status "HTML coverage report will be generated in coverage/html/"
    fi
    
    if [[ "$*" == *"--coverage-text"* ]]; then
        args="$args --coverage-text=coverage/coverage.txt"
        print_status "Text coverage report will be generated in coverage/coverage.txt"
    fi
    
    if [[ "$*" == *"--verbose"* ]]; then
        args="$args --verbose"
    fi
    
    php artisan test $args
    
    if [ $? -eq 0 ]; then
        print_success "Coverage tests completed successfully!"
    else
        print_error "Coverage tests failed!"
        exit 1
    fi
}

# Function to run tests in parallel
run_parallel_tests() {
    print_status "Running tests in parallel..."
    
    local args="--parallel"
    
    if [[ "$*" == *"--verbose"* ]]; then
        args="$args --verbose"
    fi
    
    php artisan test $args
    
    if [ $? -eq 0 ]; then
        print_success "Parallel tests completed successfully!"
    else
        print_error "Parallel tests failed!"
        exit 1
    fi
}

# Function to run specific test file
run_specific_test() {
    local test_file="$1"
    
    if [ -z "$test_file" ]; then
        print_error "Please specify a test file"
        exit 1
    fi
    
    if [ ! -f "$test_file" ]; then
        print_error "Test file not found: $test_file"
        exit 1
    fi
    
    print_status "Running specific test file: $test_file"
    
    local args="$test_file"
    
    if [[ "$*" == *"--verbose"* ]]; then
        args="$args --verbose"
    fi
    
    php artisan test $args
    
    if [ $? -eq 0 ]; then
        print_success "Test file completed successfully!"
    else
        print_error "Test file failed!"
        exit 1
    fi
}

# Function to run specific test method
run_specific_method() {
    local method_name="$1"
    
    if [ -z "$method_name" ]; then
        print_error "Please specify a test method name"
        exit 1
    fi
    
    print_status "Running specific test method: $method_name"
    
    local args="--filter=$method_name"
    
    if [[ "$*" == *"--verbose"* ]]; then
        args="$args --verbose"
    fi
    
    php artisan test $args
    
    if [ $? -eq 0 ]; then
        print_success "Test method completed successfully!"
    else
        print_error "Test method failed!"
        exit 1
    fi
}

# Function to run tests in watch mode
run_watch_tests() {
    print_status "Running tests in watch mode..."
    print_warning "Watch mode requires fswatch to be installed"
    
    if ! command -v fswatch &> /dev/null; then
        print_error "fswatch is not installed. Please install it first."
        print_status "On macOS: brew install fswatch"
        print_status "On Ubuntu: sudo apt-get install fswatch"
        exit 1
    fi
    
    print_status "Watching for changes in app/ and tests/ directories..."
    
    fswatch -o app/ tests/ | while read f; do
        print_status "Changes detected, running tests..."
        php artisan test
    done
}

# Function to run performance tests
run_performance_tests() {
    print_status "Running performance tests..."
    
    if [ ! -f "artisan" ]; then
        print_error "Laravel artisan file not found"
        exit 1
    fi
    
    # Run performance tests using artisan command
    php artisan test:performance --type=all
    
    if [ $? -eq 0 ]; then
        print_success "Performance tests completed successfully!"
    else
        print_error "Performance tests failed!"
        exit 1
    fi
}

# Main script logic
main() {
    # Check if Laravel is available
    check_laravel
    
    # Parse command
    case "${1:-help}" in
        "unit")
            run_unit_tests "$@"
            ;;
        "feature")
            run_feature_tests "$@"
            ;;
        "all")
            run_all_tests "$@"
            ;;
        "coverage")
            run_coverage_tests "$@"
            ;;
        "parallel")
            run_parallel_tests "$@"
            ;;
        "specific")
            run_specific_test "$2"
            ;;
        "method")
            run_specific_method "$2"
            ;;
        "watch")
            run_watch_tests "$@"
            ;;
        "performance")
            run_performance_tests "$@"
            ;;
        "help"|"-h"|"--help")
            show_help
            ;;
        *)
            print_error "Unknown command: $1"
            echo ""
            show_help
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@" 