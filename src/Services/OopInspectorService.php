<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RentableInterface;
use App\Contracts\SerializableEntityInterface;
use App\Models\AcademicMonograph;
use App\Models\FacultyMember;
use App\Models\InteractiveMediaBook;
use App\Models\MediaItem;
use App\Models\PremiumMember;
use App\Models\RegularMember;
use App\Models\StandardEBook;
use App\Models\Transaction;
use App\Models\User;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Class OopInspectorService
 * Menggunakan PHP 8.x Reflection API untuk memverifikasi secara langsung
 * implementasi Namespace, Class, Encapsulation, Inheritance, & Polymorphism.
 */
class OopInspectorService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function inspectArchitecture(): array
    {
        $classes = [
            MediaItem::class,
            StandardEBook::class,
            AcademicMonograph::class,
            InteractiveMediaBook::class,
            User::class,
            RegularMember::class,
            PremiumMember::class,
            FacultyMember::class,
            Transaction::class,
            RentableInterface::class,
            SerializableEntityInterface::class,
            LibraryManager::class,
            JsonStorageService::class,
        ];

        $report = [];

        foreach ($classes as $fqcn) {
            if (!class_exists($fqcn) && !interface_exists($fqcn)) {
                continue;
            }

            $ref = new ReflectionClass($fqcn);
            $parent = $ref->getParentClass();

            $properties = [];
            foreach ($ref->getProperties() as $prop) {
                if ($prop->getDeclaringClass()->getName() !== $fqcn) {
                    continue;
                }
                $visibility = match (true) {
                    $prop->isPrivate() => 'private',
                    $prop->isProtected() => 'protected',
                    default => 'public',
                };
                $type = $prop->getType() ? (string) $prop->getType() : 'mixed';
                $properties[] = sprintf('%s %s $%s', $visibility, $type, $prop->getName());
            }

            $methods = [];
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $fqcn) {
                    continue;
                }
                $returnType = $method->getReturnType() ? (string) $method->getReturnType() : 'void';
                $methods[] = sprintf('%s(): %s', $method->getName(), $returnType);
            }

            $roleTag = match (true) {
                $ref->isInterface() => 'Contract / Interface',
                $ref->isAbstract() => 'Abstract Base Class',
                $parent !== false => 'Polymorphic Subclass',
                str_contains($ref->getNamespaceName(), 'Services') => 'Domain Service',
                default => 'Domain Entity',
            };

            $report[] = [
                'fqcn' => $ref->getName(),
                'short_name' => $ref->getShortName(),
                'namespace' => $ref->getNamespaceName(),
                'role' => $roleTag,
                'parent' => $parent ? $parent->getShortName() : null,
                'interfaces' => array_map(
                    fn(ReflectionClass $i): string => $i->getShortName(),
                    $ref->getInterfaces()
                ),
                'properties' => $properties,
                'methods' => $methods,
                'file' => 'src/' . str_replace('\\', '/', substr($ref->getName(), 4)) . '.php',
            ];
        }

        return $report;
    }
}
