<?php
// filepath: c:\Users\AYMERICK\Documents\GitHub\TokyCardGames\server\tests\Service\Combat\CombatRulesServiceTest.php

namespace App\Tests\Service\Combat;

use App\Entity\Hero;
use App\Service\Combat\AttackService;
use App\Service\Combat\CombatRulesService;
use App\Service\Combat\EffectService;
use PHPUnit\Framework\TestCase;

class CombatRulesServiceTest extends TestCase
{
    private CombatRulesService $combatRulesService;
    private AttackService $mockAttackService;
    private EffectService $mockEffectService;
    
    protected function setUp(): void
    {
        // Créer des mocks pour les services dépendants
        $this->mockAttackService = $this->createMock(AttackService::class);
        $this->mockEffectService = $this->createMock(EffectService::class);
        
        // Injecter le mock AttackService dans le constructeur
        $this->combatRulesService = new CombatRulesService($this->mockAttackService);
        
        // Injecter le mock EffectService via réflexion (car il manque dans le constructeur)
        $reflectionProperty = new \ReflectionProperty(CombatRulesService::class, 'effectService');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->combatRulesService, $this->mockEffectService);
    }
    
    /**
     * Test de la méthode convertHeroToFighter
     */
    public function testConvertHeroToFighter(): void
    {
        // Créer un mock Hero
        $mockHero = $this->createMock(Hero::class);
        $mockHero->method('getId')->willReturn(1);
        $mockHero->method('getName')->willReturn('Test Hero');
        $mockHero->method('getHP')->willReturn(100);
        $mockHero->method('getATK')->willReturn(10);
        $mockHero->method('getDEF')->willReturn(5);
        $mockHero->method('getVIT')->willReturn(8);
        $mockHero->method('getRES')->willReturn(3);
        $mockHero->method('getStar')->willReturn(4);
        $mockHero->method('getType')->willReturn(2);
        
        // Convertir en combattant
        $fighter = $this->combatRulesService->convertHeroToFighter($mockHero, 5);
        
        // Assertions
        $this->assertEquals(5, $fighter['id']);
        $this->assertEquals(1, $fighter['heroId']);
        $this->assertEquals('Test Hero', $fighter['name']);
        $this->assertEquals(100, $fighter['hp']);
        $this->assertEquals(100, $fighter['maxHp']);
        $this->assertEquals(10, $fighter['attack']);
        $this->assertEquals(5, $fighter['defense']);
        $this->assertEquals(8, $fighter['speed']);
        $this->assertEquals(true, $fighter['isAlive']);
        $this->assertEmpty($fighter['statusEffects']);
    }
    
    /**
     * Test de la méthode initializeCombat
     */
    public function testInitializeCombat(): void
    {
        // Créer des mocks Hero
        $heroA = $this->createMock(Hero::class);
        $heroA->method('getId')->willReturn(1);
        $heroA->method('getName')->willReturn('Hero A');
        $heroA->method('getHP')->willReturn(100);
        $heroA->method('getATK')->willReturn(10);
        $heroA->method('getDEF')->willReturn(5);
        $heroA->method('getVIT')->willReturn(8);
        $heroA->method('getRES')->willReturn(3);
        $heroA->method('getStar')->willReturn(4);
        $heroA->method('getType')->willReturn(2);
        
        $heroB = $this->createMock(Hero::class);
        $heroB->method('getId')->willReturn(2);
        $heroB->method('getName')->willReturn('Hero B');
        $heroB->method('getHP')->willReturn(90);
        $heroB->method('getATK')->willReturn(12);
        $heroB->method('getDEF')->willReturn(4);
        $heroB->method('getVIT')->willReturn(9);
        $heroB->method('getRES')->willReturn(2);
        $heroB->method('getStar')->willReturn(3);
        $heroB->method('getType')->willReturn(1);
        
        // Initialiser le combat
        $combatState = $this->combatRulesService->initializeCombat([$heroA], [$heroB]);
        
        // Assertions
        $this->assertCount(2, $combatState['fighters']);
        $this->assertEquals('A', $combatState['fighters'][0]['team']);
        $this->assertEquals('B', $combatState['fighters'][1]['team']);
        $this->assertEquals(1, $combatState['turn']);
        $this->assertEquals(1, $combatState['round']);
        $this->assertNull($combatState['winner']);
        $this->assertEquals('start', $combatState['phase']);
    }
    
    /**
     * Test de la méthode getAttackOrder
     */
    public function testGetAttackOrder(): void
    {
        $fighters = [
            ['id' => 1, 'speed' => 5, 'isAlive' => true],
            ['id' => 2, 'speed' => 10, 'isAlive' => true],
            ['id' => 3, 'speed' => 7, 'isAlive' => false], // Mort, ne devrait pas être inclus
            ['id' => 4, 'speed' => 8, 'isAlive' => true],
        ];
        
        $turnOrder = $this->combatRulesService->getAttackOrder($fighters);
        
        // Vérifier que l'ordre est correct (basé sur la vitesse)
        // Note: nous ne pouvons pas tester l'ordre exact à cause du shuffle pour les égalités
        $this->assertCount(3, $turnOrder); // 3 combattants vivants
        $this->assertContains(1, $turnOrder);
        $this->assertContains(2, $turnOrder);
        $this->assertContains(4, $turnOrder);
        $this->assertNotContains(3, $turnOrder); // Combattant mort
    }
    
    /**
     * Test de la méthode isCombatFinished
     */
    public function testIsCombatFinished(): void
    {
        // Combat non terminé (les deux équipes ont des combattants vivants)
        $fighters1 = [
            ['team' => 'A', 'isAlive' => true],
            ['team' => 'B', 'isAlive' => true],
        ];
        $this->assertFalse($this->combatRulesService->isCombatFinished($fighters1));
        
        // Combat terminé (une seule équipe avec des combattants vivants)
        $fighters2 = [
            ['team' => 'A', 'isAlive' => true],
            ['team' => 'B', 'isAlive' => false],
        ];
        $this->assertTrue($this->combatRulesService->isCombatFinished($fighters2));
        
        // Combat terminé (aucun combattant vivant)
        $fighters3 = [
            ['team' => 'A', 'isAlive' => false],
            ['team' => 'B', 'isAlive' => false],
        ];
        $this->assertTrue($this->combatRulesService->isCombatFinished($fighters3));
    }
    
    /**
     * Test de la méthode determineWinner
     */
    public function testDetermineWinner(): void
    {
        // Équipe A gagne
        $fighters1 = [
            ['team' => 'A', 'isAlive' => true],
            ['team' => 'B', 'isAlive' => false],
        ];
        $this->assertEquals('A', $this->combatRulesService->determineWinner($fighters1));
        
        // Équipe B gagne
        $fighters2 = [
            ['team' => 'A', 'isAlive' => false],
            ['team' => 'B', 'isAlive' => true],
        ];
        $this->assertEquals('B', $this->combatRulesService->determineWinner($fighters2));
        
        // Match nul (tous morts)
        $fighters3 = [
            ['team' => 'A', 'isAlive' => false],
            ['team' => 'B', 'isAlive' => false],
        ];
        $this->assertNull($this->combatRulesService->determineWinner($fighters3));
    }
}