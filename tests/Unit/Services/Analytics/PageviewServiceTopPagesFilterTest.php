<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\PageviewService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class PageviewServiceTopPagesFilterTest extends TestCase
{
    public function testGetTopPagesAppliesPageTypeFilterWhenProvided(): void
    {
        $builder = Mockery::mock();

        DB::shouldReceive('raw')
            ->andReturnUsing(fn (string $expr) => $expr);

        DB::shouldReceive('table')
            ->once()
            ->with('pageview_analytics')
            ->andReturn($builder);

        $builder->shouldReceive('where')
            ->once()
            ->with('tenant_id', 'tenant-1')
            ->andReturnSelf();

        $builder->shouldReceive('whereBetween')
            ->once()
            ->with('date_bucket', Mockery::type('array'))
            ->andReturnSelf();

        $builder->shouldReceive('where')
            ->once()
            ->with('page_type', 'property')
            ->andReturnSelf();

        $builder->shouldReceive('select')
            ->once()
            ->andReturnSelf();

        $builder->shouldReceive('groupBy')
            ->once()
            ->with('page_slug', 'dynamic_slug', 'full_path', 'page_type')
            ->andReturnSelf();

        $builder->shouldReceive('orderByDesc')
            ->once()
            ->with('total_views')
            ->andReturnSelf();

        $builder->shouldReceive('limit')
            ->once()
            ->with(20)
            ->andReturnSelf();

        $builder->shouldReceive('get')
            ->once()
            ->andReturn(collect([]));

        $service = app(PageviewService::class);
        $result = $service->getTopPages('tenant-1', 30, 20, 'property');

        $this->assertSame([], $result);
    }
}

