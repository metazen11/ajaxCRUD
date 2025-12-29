#!/bin/bash

################################################################################
# ajaxCRUD v7.1 - Quick Installation Script
################################################################################

set -e

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║         ajaxCRUD v7.1 - Supabase-Level Features               ║"
echo "║         Quick Installation Script                              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Check if Docker is available
if command -v docker &> /dev/null && command -v docker-compose &> /dev/null; then
    echo "✓ Docker and Docker Compose detected"
    echo ""
    echo "Choose installation method:"
    echo "  1) Docker (recommended for quick demo)"
    echo "  2) Manual installation"
    echo ""
    read -p "Enter choice (1 or 2): " choice
else
    choice=2
fi

if [ "$choice" = "1" ]; then
    echo ""
    echo "🐳 Starting Docker containers..."
    echo ""
    docker-compose up -d
    
    echo ""
    echo "✅ Installation complete!"
    echo ""
    echo "Services started:"
    echo "  • Web Server: http://localhost:8080"
    echo "  • phpMyAdmin: http://localhost:8081"
    echo "  • MySQL: localhost:3306"
    echo ""
    echo "Try these demos:"
    echo "  • Supabase Features: http://localhost:8080/examples/demo_supabase_features.php"
    echo "  • REST API: http://localhost:8080/examples/api-demo.php"
    echo ""
    echo "To stop: docker-compose down"
    
else
    echo ""
    echo "📦 Manual installation..."
    echo ""
    
    # Check PHP version
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php -r 'echo PHP_VERSION;')
        echo "✓ PHP $PHP_VERSION detected"
        
        # Check if version is >= 8.1
        if php -r 'exit(version_compare(PHP_VERSION, "8.1.0", "<") ? 1 : 0);'; then
            echo "✓ PHP version is compatible (>= 8.1)"
        else
            echo "⚠️  WARNING: PHP 8.1+ required, you have $PHP_VERSION"
        fi
    else
        echo "⚠️  PHP not found. Please install PHP 8.1 or higher"
        exit 1
    fi
    
    # Check for Composer
    if command -v composer &> /dev/null; then
        echo "✓ Composer detected"
        echo ""
        read -p "Install via Composer? (y/n): " use_composer
        
        if [ "$use_composer" = "y" ]; then
            echo ""
            echo "Installing ajaxCRUD via Composer..."
            composer require ajaxcrud/ajaxcrud
            echo ""
            echo "✅ Installation complete!"
            echo ""
            echo "Add to your PHP file:"
            echo "  require 'vendor/autoload.php';"
        else
            echo ""
            echo "Manual setup - files are ready to use"
        fi
    else
        echo "• Composer not found (optional)"
        echo ""
        echo "Manual setup - files are ready to use"
    fi
    
    echo ""
    echo "Next steps:"
    echo ""
    echo "1. Configure database in preheader.php:"
    echo "   \$DB_DRIVER = 'mysql';"
    echo "   \$DB_CONFIG = ['mysql' => [...]]; "
    echo ""
    echo "2. Create audit table (optional):"
    echo "   AuditLog::createTable();"
    echo ""
    echo "3. Setup auth (optional):"
    echo "   \$rbac = new RoleBasedRBAC(\$_SESSION['user_id'], 'admin');"
    echo "   AuthManager::getInstance()->init(\$rbac);"
    echo ""
    echo "4. View examples:"
    echo "   examples/demo_supabase_features.php"
    echo "   examples/api-demo.php"
    echo ""
    echo "📖 Documentation:"
    echo "   • Quick Start: QUICKSTART.md"
    echo "   • Features: SUPABASE_FEATURES.md"
    echo "   • Changelog: CHANGELOG.md"
fi

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "Thank you for using ajaxCRUD! 🚀"
echo "═══════════════════════════════════════════════════════════════"
