<?php

require_once 'vendor/autoload.php';

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Doctrine\ORM\EntityManager;

// Test entity manager directly
echo "=== Entity Manager Test ===\n\n";

// Bootstrap Symfony
$kernel = new class('dev', true) extends Kernel {
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir().'/config/services.yaml');
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }
};

try {
    $kernel->boot();
    $container = $kernel->getContainer();
    
    // Get entity manager
    $entityManager = $container->get('doctrine.orm.entity_manager');
    echo "✅ Entity manager obtained\n";
    
    // Test creating a user directly
    echo "\n🧪 Testing direct user creation...\n";
    
    $user = new \App\Entity\User();
    $user->setFirstName('Test');
    $user->setLastName('User');
    $user->setEmail('test_' . time() . '@example.com');
    $user->setPhoneNumber('12345678');
    $user->setPassword('Password123');
    $user->setRoleId(1);
    $user->setRoles(['ROLE_CANDIDATE']);
    $user->setVerified(false);
    
    echo "✅ User entity created\n";
    
    // Test persist
    $entityManager->persist($user);
    echo "✅ User persisted\n";
    
    // Test flush
    $entityManager->flush();
    echo "✅ Entity manager flushed\n";
    
    // Get user ID
    $userId = $user->getId();
    echo "✅ User ID: $userId\n";
    
    // Verify user exists
    $savedUser = $entityManager->getRepository(\App\Entity\User::class)->find($userId);
    if ($savedUser) {
        echo "✅ User verified in database\n";
        echo "   - Email: " . $savedUser->getEmail() . "\n";
        echo "   - Name: " . $savedUser->getFullName() . "\n";
        
        // Clean up
        $entityManager->remove($savedUser);
        $entityManager->flush();
        echo "🧹 Test user cleaned up\n";
    } else {
        echo "❌ User not found after save\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
