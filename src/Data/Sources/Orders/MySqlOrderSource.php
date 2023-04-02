<?php

namespace App\Data\Sources\Orders;

use App\Core\Utils\Failures\NotFoundFailure;
use App\Core\Utils\Failures\ServerFailure;
use App\Data\Sources\Orders\OrderSourceInterface;
use App\Data\Models\OrderModel;
use App\Data\Models\ClientModel;
use App\Data\Models\CarModel;
use DateTime;
use PDO;

class MySqlOrderSource implements OrderSourceInterface
{

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // * startAt and endAt defines an interval, they are optional
    public function findAll(?DateTime $startAt = null, ?DateTime $endAt = null): array
    {
        $orderTableName = OrderModel::TABLE_NAME;
        $clientTableName = ClientModel::TABLE_NAME;
        $carTableName = CarModel::TABLE_NAME;
        $orderCarsTableName = "order_cars";

        $intervalQuery = "";

        if ($startAt && $endAt) {
            $intervalQuery =
                "WHERE $orderTableName.createdAt
                    BETWEEN :startAt AND :endAt ";
        } else if ($startAt) {
            $intervalQuery = "WHERE $orderTableName.createdAt >= :startAt ";
        } else if ($endAt) {
            $intervalQuery = "WHERE $orderTableName.createdAt <= :endAt";
        }

        $statement = $this->pdo->prepare(
            "SELECT
                $orderTableName.id AS orderId,
                $orderTableName.clientId,
                $orderTableName.createdAt AS orderCreatedAt,
                $orderTableName.updatedAt As orderUpdatedAt,
                $clientTableName.id AS clientId,
                $clientTableName.name AS clientName,
                $clientTableName.contact AS clientContact,
                $clientTableName.createdAt AS clientCreatedAt,
                $clientTableName.updatedAt AS clientUpdatedAt,
                $carTableName.id AS carId,
                $carTableName.price AS carPrice,
                $carTableName.inStock AS carInStock,
                $carTableName.name AS carName,
                $carTableName.createdAt AS carCreatedAt,
                $carTableName.updatedAt AS carUpdatedAt,
                $orderCarsTableName.quantity
            FROM $orderTableName
            INNER JOIN $clientTableName
                ON $orderTableName.clientId = $clientTableName.id
            INNER JOIN $orderCarsTableName
                ON $orderTableName.id = $orderCarsTableName.orderId
            INNER JOIN $carTableName
                ON $orderCarsTableName.carId = $carTableName.id
            $intervalQuery
            ORDER BY
                $orderTableName.createdAt DESC,
                $clientTableName.name ASC,
                $carTableName.name ASC
            ;"
        );

        if ($startAt) {
            $statement->bindValue("startAt", $startAt->format(DateTime::ATOM));
        }

        if ($endAt) {
            $statement->bindValue("endAt", $endAt->format(DateTime::ATOM));
        }

        $statement->execute();
        $arrayFetched = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $this->createOrdersFromArrayFetched($arrayFetched);
    }

    public function findById(string $id): OrderModel
    {
        $orderTableName = OrderModel::TABLE_NAME;
        $clientTableName = ClientModel::TABLE_NAME;
        $carTableName = CarModel::TABLE_NAME;
        $orderCarsTableName = "order_cars";

        $intervalQuery = "";

        $statement = $this->pdo->prepare(
            "SELECT
                $orderTableName.id AS orderId,
                $orderTableName.clientId,
                $orderTableName.createdAt AS orderCreatedAt,
                $orderTableName.updatedAt As orderUpdatedAt,
                $clientTableName.id AS clientId,
                $clientTableName.name AS clientName,
                $clientTableName.contact AS clientContact,
                $clientTableName.createdAt AS clientCreatedAt,
                $clientTableName.updatedAt AS clientUpdatedAt,
                $carTableName.id AS carId,
                $carTableName.price AS carPrice,
                $carTableName.inStock AS carInStock,
                $carTableName.name AS carName,
                $carTableName.createdAt AS carCreatedAt,
                $carTableName.updatedAt AS carUpdatedAt,
                $orderCarsTableName.quantity
            FROM $orderTableName
            INNER JOIN $clientTableName
                ON $orderTableName.clientId = $clientTableName.id
            INNER JOIN $orderCarsTableName
                ON $orderTableName.id = $orderCarsTableName.orderId
            INNER JOIN $carTableName
                ON $orderCarsTableName.carId = $carTableName.id
            WHERE $orderTableName.id = :id
            ORDER BY
                $orderTableName.createdAt DESC,
                $clientTableName.name ASC,
                $carTableName.name ASC
            ;"
        );
        $statement->bindValue("id", $id);
        $statement->execute();

        $arrayFetched = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($arrayFetched)) {
            throw new NotFoundFailure();
        }

        return $this->createOrdersFromArrayFetched($arrayFetched)[0];
    }

    public function findByClientId(string $clientId): array
    {
        /* $orderTableName = OrderModel::TABLE_NAME;
        $clientTableName = ClientModel::TABLE_NAME;
        $carTableName = CarModel::TABLE_NAME;
        $statement = $this->pdo->prepare(
        "SELECT
        $orderTableName.id AS orderId,
        $orderTableName.clientId,
        $orderTableName.carId,
        $orderTableName.quantity,
        $orderTableName.createdAt,
        $orderTableName.updatedAt,
        $clientTableName.name AS clientName,
        $clientTableName.contact AS clientContact,
        $clientTableName.createdAt AS clientCreatedAt,
        $clientTableName.updatedAt AS clientUpdatedAt,
        $carTableName.price AS carPrice,
        $carTableName.inStock AS carInStock,
        $carTableName.name AS carName,
        $carTableName.createdAt AS carCreatedAt,
        $carTableName.updatedAt AS carUpdatedAt
        FROM $orderTableName
        INNER JOIN $clientTableName
        ON $orderTableName.clientId = $clientTableName.id
        INNER JOIN $carTableName
        ON $orderTableName.carId = $carTableName.id
        WHERE $clientTableName.id = :clientId
        ORDER BY
        $orderTableName.createdAt DESC,
        $clientTableName.name ASC,
        $carTableName.name ASC;"
        );
        $statement->bindValue("clientId", $clientId);
        $statement->execute();
        $arrayFetched = $statement->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function ($fetched) {
        return (
        new OrderModel(
        $fetched["orderId"],
        new ClientModel(
        $fetched["clientId"],
        $fetched["clientName"],
        $fetched["clientContact"],
        $fetched["clientCreatedAt"],
        $fetched["clientUpdatedAt"]
        ),
        new CarModel(
        $fetched["carId"],
        $fetched["carName"],
        $fetched["carPrice"],
        $fetched["carInStock"],
        $fetched["carCreatedAt"],
        $fetched["carUpdatedAt"]
        ),
        $fetched["quantity"],
        $fetched["createdAt"],
        $fetched["updatedAt"]
        )
        )->getRaw();
        }, $arrayFetched); */
        return [];
    }

    public function save(
        string $id,
        string $clientId,
        array $orderCarsIds,
        array $carsIds,
        array $quantities,
        string $createdAt,
        string $updatedAt
    ): void {
        $orderTableName = OrderModel::TABLE_NAME;
        $orderCarsTableName = "order_cars";

        // save the order
        $statement = $this->pdo->prepare(
            "INSERT INTO $orderTableName 
                (id, clientId, createdAt, updatedAt)
            VALUES
                (:id, :clientId, :createdAt, :updatedAt);"
        );
        $statement->bindValue("id", $id);
        $statement->bindValue("clientId", $clientId);
        $statement->bindValue("createdAt", $createdAt);
        $statement->bindValue("updatedAt", $updatedAt);
        $statement->execute();

        $length = count($orderCarsIds);
        for ($i = 0; $i < $length; $i++) {
            $statement = $this->pdo->prepare(
                "INSERT INTO $orderCarsTableName
                    (id, orderId, carId, quantity, createdAt, updatedAt)
                VALUES
                    (:id, :orderId, :carId, :quantity, :createdAt, :updatedAt);"
            );

            $statement->bindValue("id", $orderCarsIds[$i]);
            $statement->bindValue("orderId", $id);
            $statement->bindValue("carId", $carsIds[$i]);
            $statement->bindValue("quantity", $quantities[$i]);
            $statement->bindValue("createdAt", $createdAt);
            $statement->bindValue("updatedAt", $updatedAt);
            $statement->execute();
        }
    }

    public function update(
        string $id,
        string $clientId,
        array $orderCarsIds,
        array $carsIds,
        array $quantities,
        string $createdAt,
        string $updatedAt
    ): void {
        throw new ServerFailure("Not implemented yet.");
    }

    /*
    for this case, deleting an order === cancelling the order,
    so all cars in the order must be returned to the stock
    ! if deleting an order === deleting only the order, the logic won't work
    */
    public function delete(string $id): void
    {
        $orderTableName = OrderModel::TABLE_NAME;
        $carTableName = CarModel::TABLE_NAME;
        $orderCarsTableName = "order_cars";

        $now = new DateTime();

        $statement = $this->pdo->prepare(
            "SELECT * FROM $orderCarsTableName WHERE orderId = :id;"
        );
        $statement->bindValue("id", $id);
        $statement->execute();
        $orderCars = $statement->fetchAll(PDO::FETCH_ASSOC);

        // return all cars to the stock
        foreach ($orderCars as $orderCar) {
            $statement = $this->pdo->prepare(
                "UPDATE $carTableName
                SET inStock = inStock + :quantity,
                    updatedAt = :updatedAt
                WHERE id = :carId;"
            );

            $statement->bindValue("quantity", $orderCar["quantity"]);
            $statement->bindValue("updatedAt", $now->format(DateTime::ATOM));
            $statement->bindValue("carId", $orderCar["carId"]);
            $statement->execute();
        }

        // delete the orderCars
        $statement = $this->pdo->prepare(
            "DELETE FROM $orderCarsTableName WHERE orderId = :orderId;"
        );
        $statement->bindValue("orderId", $id);
        $statement->execute();

        // delete the order
        $statement = $this->pdo->prepare(
            "DELETE FROM $orderTableName WHERE id = :id;"
        );
        $statement->bindValue("id", $id);
        $statement->execute();
    }

    private function createOrdersFromArrayFetched($arrayFetched)
    {
        $orderIdArr = [];

        foreach ($arrayFetched as $fetched) {
            if (array_key_exists($fetched["orderId"], $orderIdArr)) {
                $orderIdArr[$fetched["orderId"]]["cars"][$fetched["carId"]] =
                    [
                        "car" => [
                            "id" => $fetched["carId"],
                            "price" => $fetched["carPrice"],
                            "inStock" => $fetched["carInStock"],
                            "name" => $fetched["carName"],
                            "createdAt" => $fetched["carCreatedAt"],
                            "updatedAt" => $fetched["carUpdatedAt"],
                        ],
                        "quantity" => $fetched["quantity"]
                    ];
            } else {
                $orderIdArr[$fetched["orderId"]] = [
                    "id" => $fetched["orderId"],
                    "clientId" => $fetched["clientId"],
                    "createdAt" => $fetched["orderCreatedAt"],
                    "updatedAt" => $fetched["orderUpdatedAt"],
                    "client" => [
                        "id" => $fetched["clientId"],
                        "name" => $fetched["clientName"],
                        "contact" => $fetched["clientContact"],
                        "createdAt" => $fetched["clientCreatedAt"],
                        "updatedAt" => $fetched["clientUpdatedAt"],
                    ],
                    "cars" => [
                        $fetched["carId"] => [
                            "car" => [
                                "id" => $fetched["carId"],
                                "price" => $fetched["carPrice"],
                                "inStock" => $fetched["carInStock"],
                                "name" => $fetched["carName"],
                                "createdAt" => $fetched["carCreatedAt"],
                                "updatedAt" => $fetched["carUpdatedAt"],
                            ],
                            "quantity" => $fetched["quantity"]
                        ],
                    ]
                ];
            }
        }

        return array_values(
            array_map(
                function ($orderArr) {
                    return (new OrderModel(
                            $orderArr["id"],
                            new ClientModel(
                                $orderArr["client"]["id"],
                                $orderArr["client"]["name"],
                                $orderArr["client"]["contact"],
                                $orderArr["client"]["createdAt"],
                                $orderArr["client"]["updatedAt"]
                            ),
                            array_map(
                                function ($carIdArr) {
                                    $car = $carIdArr["car"];
                                    $quantity = $carIdArr["quantity"];

                                    return [
                                        "car" => new CarModel(
                                            $car["id"],
                                            $car["name"],
                                            $car["price"],
                                            $car["inStock"],
                                            $car["createdAt"],
                                            $car["updatedAt"],
                                        ),
                                        "quantity" => $quantity
                                    ];
                                },
                                array_values($orderArr["cars"])
                            ),
                            $orderArr["createdAt"],
                            $orderArr["updatedAt"]
                        )
                    );
                },
                $orderIdArr
            )
        );
    }

    public function getCount(): int
    {
        $orderTableName = OrderModel::TABLE_NAME;

        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) AS ordersCount FROM $orderTableName;"
        );

        $statement->execute();

        $fetched = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $fetched[0]["ordersCount"];
    }

    public function getCountByLastMonths(int $lastMonths): int
    {
        $orderTableName = OrderModel::TABLE_NAME;

        $statement = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS ordersCount
            FROM $orderTableName
            WHERE
                createdAt >= DATE_SUB(
                    CURDATE(),
                    INTERVAL :lastMonths MONTH
                );"
        );

        $statement->bindValue("lastMonths", $lastMonths);

        $statement->execute();

        $fetched = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $fetched[0]["ordersCount"];
    }

    public function getRevenue(): int
    {
        $orderTableName = OrderModel::TABLE_NAME;
        $carTableName = CarModel::TABLE_NAME;
        $orderCarsTableName = "order_cars";

        $statement = $this->pdo->prepare(
            "SELECT
                $carTableName.price,
                $orderCarsTableName.quantity
            FROM $orderTableName
            INNER JOIN $orderCarsTableName
                ON $orderTableName.id = $orderCarsTableName.orderId
            INNER JOIN $carTableName
                ON $orderCarsTableName.carId = $carTableName.id
            ;"
        );

        $statement->execute();

        $arrayFetched = $statement->fetchAll(PDO::FETCH_ASSOC);

        return array_reduce(
            $arrayFetched,
            function ($prev, $priceQuantity) {
                return $prev + $priceQuantity["price"] * $priceQuantity["quantity"];
            },
            0
        );
    }

    public function getRevenueByLastMonths(int $lastMonths): int
    {
        $orderTableName = OrderModel::TABLE_NAME;
        $carTableName = CarModel::TABLE_NAME;
        $orderCarsTableName = "order_cars";

        $statement = $this->pdo->prepare(
            "SELECT
                $carTableName.price,
                $orderCarsTableName.quantity
            FROM $orderTableName
            INNER JOIN $orderCarsTableName
                ON $orderTableName.id = $orderCarsTableName.orderId
            INNER JOIN $carTableName
                ON $orderCarsTableName.carId = $carTableName.id
            WHERE
                $orderTableName.createdAt >= DATE_SUB(
                    CURDATE(),
                    INTERVAL :lastMonths MONTH
                );"
        );

        $statement->bindValue("lastMonths", $lastMonths);

        $statement->execute();

        $arrayFetched = $statement->fetchAll(PDO::FETCH_ASSOC);

        return array_reduce(
            $arrayFetched,
            function ($prev, $priceQuantity) {
                return $prev + $priceQuantity["price"] * $priceQuantity["quantity"];
            },
            0
        );
    }
}
