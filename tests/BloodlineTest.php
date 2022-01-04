<?php

namespace Tests;

use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Bloodline;
use Tests\Models\User;

class BloodlineTest extends TestCase
{
    public function testLazyLoading()
    {
        $bloodline = User::find(5)->bloodline()->breadthFirst()->get();

        $this->assertEquals([1, 2, 5, 8], $bloodline->pluck('id')->all());
        $this->assertEquals([-2, -1, 0, 1], $bloodline->pluck('depth')->all());
        $this->assertEquals(['5.2.1', '5.2', '5', '5.8'], $bloodline->pluck('path')->all());
        $this->assertEquals(['user-5/user-2/user-1', 'user-5/user-2', 'user-5', 'user-5/user-8'], $bloodline->pluck('slug_path')->all());
    }

    public function testEagerLoading()
    {
        $users = User::with(
            [
                'bloodline' => function (Bloodline $relation) {
                    $relation->breadthFirst()->orderBy('id');
                },
            ]
        )->get();

        $this->assertEquals(range(1, 9), $users[0]->bloodline->pluck('id')->all());
        $this->assertEquals([1, 2, 5, 8], $users[1]->bloodline->pluck('id')->all());
        $this->assertEquals([1, 2, 5, 8], $users[4]->bloodline->pluck('id')->all());
        $this->assertEquals(['5.2.1', '5.2', '5', '5.8'], $users[4]->bloodline->pluck('path')->all());
        $this->assertEquals(['user-5/user-2/user-1', 'user-5/user-2', 'user-5', 'user-5/user-8'], $users[4]->bloodline->pluck('slug_path')->all());
    }

    public function testLazyEagerLoading()
    {
        $users = User::all()->load(
            [
                'bloodline' => function (Bloodline $relation) {
                    $relation->breadthFirst()->orderBy('id');
                },
            ]
        );

        $this->assertEquals(range(1, 9), $users[0]->bloodline->pluck('id')->all());
        $this->assertEquals([1, 2, 5, 8], $users[1]->bloodline->pluck('id')->all());
        $this->assertEquals([1, 2, 5, 8], $users[4]->bloodline->pluck('id')->all());
        $this->assertEquals(['5.2.1', '5.2', '5', '5.8'], $users[4]->bloodline->pluck('path')->all());
        $this->assertEquals(['user-5/user-2/user-1', 'user-5/user-2', 'user-5', 'user-5/user-8'], $users[4]->bloodline->pluck('slug_path')->all());
    }

    public function testExistenceQuery()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $descendants = User::first()->descendants()->has('bloodline', '<', 4)->get();

        $this->assertEquals([4, 7], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::has('bloodline', '<', 4)->get();

        $this->assertEquals([4, 7, 11, 12], $users->pluck('id')->all());
    }

    public function testUpdate()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        $affected = User::find(5)->bloodline()->delete();

        $this->assertEquals(4, $affected);
        $this->assertNotNull(User::withTrashed()->find(1)->deleted_at);
        $this->assertNotNull(User::withTrashed()->find(8)->deleted_at);
        $this->assertNull(User::find(3)->deleted_at);
    }
}
