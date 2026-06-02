<?php

namespace DarkOak\Http\Controllers\Api\Application\Domains;

use DarkOak\Models\DomainRoot;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Domains\StoreDomainRootRequest;
use DarkOak\Http\Requests\Api\Application\Domains\UpdateDomainRootRequest;
use DarkOak\Http\Requests\Api\Application\Domains\DeleteDomainRootRequest;
use DarkOak\Http\Requests\Api\Application\Domains\GetDomainRootsRequest;
use DarkOak\Transformers\Api\Application\Domains\DomainRootTransformer;
use DarkOak\Services\Domains\CloudflareService;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Cache;

class DomainRootController extends ApplicationApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(GetDomainRootsRequest $request): array
    {
        $roots = QueryBuilder::for(DomainRoot::query())
            ->allowedFilters(['name', 'root_domain', AllowedFilter::exact('provider'), AllowedFilter::exact('is_active')])
            ->allowedSorts(['name', 'root_domain', 'provider', 'is_active', 'created_at'])
            ->paginate(min((int) $request->query('per_page', 50), 100));

        return $this->fractal->collection($roots)
            ->transformWith(DomainRootTransformer::class)
            ->toArray();
    }

    public function store(StoreDomainRootRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['provider_config'] = $data['provider_config'] ?? [];

        $root = DomainRoot::query()->create($data);

        $cfError = null;
        if ($root->isCloudflare()) {
            $cfError = $this->verifyCloudflare($root);
        }

        Activity::event('admin:domain-roots:create')
            ->property('domain_root', $root)
            ->description('A domain root was created')
            ->log();

        $response = $this->fractal->item($root->refresh())
            ->transformWith(DomainRootTransformer::class)
            ->respond(JsonResponse::HTTP_CREATED);

        if ($cfError) {
            $data = $response->getData(true);
            $data['cloudflare_warning'] = $cfError;
            $response->setData($data);
        }

        return $response;
    }

    public function view(GetDomainRootsRequest $request, DomainRoot $domainRoot): array
    {
        return $this->fractal->item($domainRoot)
            ->transformWith(DomainRootTransformer::class)
            ->toArray();
    }

    public function update(UpdateDomainRootRequest $request, DomainRoot $domainRoot): array
    {
        $data = $request->validated();

        if (isset($data['provider_config'])) {
            $existing = $domainRoot->provider_config ?? [];
            $data['provider_config'] = array_merge($existing, $data['provider_config']);
        }

        $domainRoot->updateOrFail($data);

        if ($domainRoot->isCloudflare()) {
            $this->verifyCloudflare($domainRoot);
        }

        Activity::event('admin:domain-roots:update')
            ->property('domain_root', $domainRoot)
            ->property('changes', $data)
            ->description('A domain root was updated')
            ->log();

        return $this->fractal->item($domainRoot->refresh())
            ->transformWith(DomainRootTransformer::class)
            ->toArray();
    }

    public function delete(DeleteDomainRootRequest $request, DomainRoot $domainRoot): Response
    {
        $rootName = $domainRoot->name;
        $domainRoot->delete();

        Activity::event('admin:domain-roots:delete')
            ->property('name', $rootName)
            ->description('A domain root was deleted')
            ->log();

        return $this->returnNoContent();
    }

    public function sync(DomainRoot $domainRoot): JsonResponse
    {
        if (!$domainRoot->isCloudflare()) {
            return new JsonResponse(['success' => false, 'message' => 'This domain root does not use Cloudflare.'], 400);
        }

        try {
            $cf = CloudflareService::fromConfig($domainRoot->provider_config ?? []);
            $config = $domainRoot->provider_config ?? [];

            $result4 = $cf->createDnsRecord('*', $domainRoot->root_domain, array_merge($config, ['record_type' => 'A']));

            $result6 = null;
            if (!empty($config['origin_ipv6'])) {
                $result6 = $cf->createDnsRecord('*', $domainRoot->root_domain, array_merge($config, ['record_type' => 'AAAA']));
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'DNS records synced successfully.',
                'details' => ['a_record_created' => $result4, 'aaaa_record_created' => $result6],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Failed to sync DNS records: ' . $e->getMessage()], 500);
        }
    }

    private function verifyCloudflare(DomainRoot $root): ?string
    {
        try {
            $config = $root->provider_config ?? [];
            if (empty($config['api_token']) || empty($config['zone_id'])) {
                return 'Cloudflare API token and Zone ID are required. DNS automation will not work.';
            }

            $cf = CloudflareService::fromConfig($config);
            if (!$cf->verifyCredentials()) {
                return 'Cloudflare credentials could not be verified. Check your API token and Zone ID.';
            }
        } catch (\Exception $e) {
            return 'Cloudflare verification failed: ' . $e->getMessage();
        }

        return null;
    }
}