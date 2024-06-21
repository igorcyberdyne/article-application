<?php

namespace App\Tools;


use closure;
use DateTime;
use InvalidArgumentException;

class PaginationProcessor
{
    const MAX_ROWS = 50;
    private closure $data;
    private closure $count;
    private array $filter;
    private int $limit;
    private int $offset;
    private string $searchKey;

    public function __construct(
        ?array $filter = [],
        ?int $limit = null,
        ?int $offset = null,
        private ?string $paginatorUrl = null,
        private int $maxRows = self::MAX_ROWS,
    )
    {
        foreach ([$limit, $offset] as $arg) {
            if ($arg == 0 || empty($arg) || 1 === preg_match('/^[0-9]+$/', $arg)) {
                continue;
            }

            throw new InvalidArgumentException("The param 'limit' or 'offset' must be an integer");
        }

        $this->filter = empty($filter) ? [] : $filter;

        $this->maxRows = min($this->maxRows, self::MAX_ROWS);

        $this->offset = is_null($offset) ? 0 : $offset;
        $this->limit = is_null($limit) ? $maxRows : min($limit, $maxRows);


        // Build search key
        $this->paginatorUrl = !empty($this->paginatorUrl) ? trim($paginatorUrl) : null;
        $query = http_build_query([
            "limit" => $this->limit,
            "offset" => $this->offset,
        ]);
        $this->searchKey = Tools::getSlug((new DateTime())->format("Y-m-d"), $this->paginatorUrl ?? "", "?", $query, json_encode($this->filter));
    }

    public function setData(closure $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function setCount(closure $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function getSearchKey(): string
    {
        return $this->searchKey;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        $data = call_user_func($this->data, $this->filter, $this->limit, $this->offset);
        $total = call_user_func($this->count, $this->filter, $this->limit);

        $currentPage = $this->limit <= 0 ? 1 : floor($this->offset / $this->limit) + 1;
        $totalPage = $this->limit <= 0 ? 0 : ceil($total / $this->limit);


        $paginator = [
            "total" => $total,
            "currentPage" => $total == 0 ? 0 : $currentPage,
            "totalPage" => $totalPage,
        ];

        if (!empty($this->paginatorUrl)) {

            $nextPageUrl = null;
            $offsetForNextPage = $this->offset + $this->limit;
            if ($offsetForNextPage < $total) {
                $query = http_build_query([
                    "limit" => $this->limit,
                    "offset" => $offsetForNextPage,
                ]);
                $nextPageUrl = $this->paginatorUrl . "?" . $query;
            }


            $previousPageUrl = null;
            $offsetForPreviousPage = $this->offset < $this->limit ? 0 : $this->offset - $this->limit;
            if ($offsetForPreviousPage > 0 || $currentPage > 1) {
                $query = http_build_query([
                    "limit" => $this->limit,
                    "offset" => $offsetForPreviousPage,
                ]);
                $previousPageUrl = $this->paginatorUrl . "?" . $query;
            }

            $paginator = array_merge($paginator, [
                "nextPage" => $nextPageUrl,
                "previousPage" => $previousPageUrl,
            ]);
        }

        $paginator['resultCount'] = count($data);

        return [
            "paginator" => $paginator,
            "result" => $data,
        ];
    }
}