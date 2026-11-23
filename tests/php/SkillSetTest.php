<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    Skill,
    SkillType,
    SkillSet,
};

final class SkillSetTest extends TestCase
{
    private function assertThrows(Closure $c, string $msg = "should have thrown exception"): void {
        try {
            $c();
            $this->assertTrue(false, $msg);
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, $msg);
        }
    }

    public function testEmpty() : void {
        $ss = new SkillSet();

        $this->assertTrue($ss->isEmpty(), "empty set should be empty");
        $this->assertEquals([], $ss->allItems());
        $this->assertEquals(0, $ss->size());
        $this->assertEquals(null, $ss->skillById(1));
    }

    public function testBasic() : void{
        $ss = new SkillSet();
        $s1 = new Skill(3, SkillType::ANVIL);
        $s2 = new Skill(5, SkillType::PICKAXE);
        $ss->add($s1);
        $ss->add($s2);

        $this->assertEquals(2, $ss->size());
        $this->assertEquals($s1, $ss->skillById(3));
        $this->assertEquals($s1, $ss->skillById(3));
        $this->assertEquals($s2, $ss->skillById(5));
        $this->assertEquals(null, $ss->skillById(1));
        $this->assertEquals(2, $ss->size());
    }

    public function testAddAndTake(): void {
        $ss = new SkillSet();
        $s1 = new Skill(3, SkillType::ANVIL);
        $s2 = new Skill(5, SkillType::PICKAXE);
        $ss->add($s1);
        $ss->add($s2);

        $this->assertEquals(2, $ss->size());
        $this->assertEquals($s1, $ss->takeSkillById(3));
        $this->assertEquals(null, $ss->skillById(3));
        $this->assertEquals(1, $ss->size());
        $this->assertEquals($s2, $ss->takeSkillById(5));
        $this->assertTrue($ss->isEmpty(), "should be empty");
        $this->assertEquals(0, $ss->size());
    }

    public function testNoDupes(): void {
        $ss = new SkillSet();
        $s1 = new Skill(3, SkillType::ANVIL);
        $s2 = new Skill(3, SkillType::PICKAXE);
        $ss->add($s1);
        $this->assertThrows(fn() => $ss->add($s1));
        $this->assertThrows(fn() => $ss->add($s2));
    }

    public function testTakeN(): void {
        $ss = new SkillSet();
        $s1 = new Skill(3, SkillType::ANVIL);
        $s2 = new Skill(5, SkillType::PICKAXE);
        $s3 = new Skill(7, SkillType::ANVIL);

        $ss = SkillSet::of($s1, $s2, $s3);

        $got = $ss->takeN(1);
        $this->assertEquals(2, $ss->size());
        $this->assertEquals(1, $got->size());

        $got = $ss->takeN(5);
        $this->assertEquals(0, $ss->size());
        $this->assertEquals(2, $got->size());
    }

    public function testAddSet() : void{
        $ss = new SkillSet();
        $s1 = new Skill(3, SkillType::ANVIL);
        $s2 = new Skill(5, SkillType::PICKAXE);
        $ss->add($s1);
        $ss->add($s2);

        $ss2 = new SkillSet();
        $s3 = new Skill(7, SkillType::ANVIL);
        $ss2->add($s3);

        $ss2->addSet($ss);

        $this->assertEquals(2, $ss->size());
        $this->assertEquals(3, $ss2->size());
        $this->assertEquals($s1, $ss2->skillById(3));
        $this->assertEquals($s2, $ss2->skillById(5));
        $this->assertEquals($s3, $ss2->skillById(7));

        $this->assertEquals($s1, $ss->skillById(3));
        $this->assertEquals($s2, $ss->skillById(5));
        $this->assertEquals(null, $ss->skillById(7));
    }
}
