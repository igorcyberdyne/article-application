<?php

namespace App\Tools;


use closure;
use InvalidArgumentException;

class PaginationProcessor
{
    const MAX_ROWS = 50;
    private closure $data;
    private closure $count;
    private array $filter;
    private int $limit;
    private int $offset;

    public function __construct(
        $limit,
        $offset,
        private int $maxRows = self::MAX_ROWS,
        private ?string $paginatorUrl = null,
    )
    {
        foreach ([$limit, $offset] as $arg) {
            if (empty($arg) || 1 === preg_match('/^[0-9]+$/', $arg)) {
                continue;
            }

            throw new InvalidArgumentException("The param 'limit' or 'offset' must be an integer");
        }


        $this->maxRows = min($this->maxRows, self::MAX_ROWS);

        $this->offset = !empty($offset) ? $offset : 0;
        $this->limit = !empty($limit) ? (($limit) <= $maxRows ? $limit : $maxRows) : $maxRows;

        $this->paginatorUrl = !empty($this->paginatorUrl) ? trim($paginatorUrl) : null;
    }

    public function setFilter(?array $filter = []): static
    {
        $this->filter = empty($filter) ? [] : $filter;

        return $this;
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

    /**
     * @return array
     */
    public function getResult(): array
    {
        $data = call_user_func($this->data, $this->filter, $this->limit, $this->offset);
        $total = call_user_func($this->count, $this->filter);


        $currentPage = floor($this->offset / $this->limit) + 1;
        $totalPage = floor($total / $this->limit) + 1;

        $paginator = [
            "total" => $total,
            "currentPage" => $currentPage,
            "totalPage" => $totalPage,
        ];

        if (!empty($this->paginatorUrl)) {

            $nextPageUrl = null;
            $offsetForNextPage = $this->offset + $this->limit;
            if ($offsetForNextPage <= $total) {
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