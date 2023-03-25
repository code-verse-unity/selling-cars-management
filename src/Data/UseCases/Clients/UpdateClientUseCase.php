<?php

namespace App\Data\UseCases\Clients;

use App\Core\Utils\Failures\ServerFailure;
use App\Core\Utils\Strings\RandomString;
use App\Domain\Repositories\ClientRepositoryInterface;
use App\Data\Models\ClientModel;
use App\Core\Utils\Failures\Failure;
use DateTime;

class UpdateClientUseCase
{
  private ClientRepositoryInterface $clientRepository;

  public function __construct(ClientRepositoryInterface $clientRepository)
  {
    $this->clientRepository = $clientRepository;
  }

  public function execute($id, $name, $contact)
  {
    try {
      // we get an intance of Client, otherwise an NotFoundFailure will be thrown
      $client = $this->repository->findById($id);
      
      // we use the setters, updatedAt will be updated automatically
      $client->setName($name);
      $client->setContact($contact);

      // then we update on the repository
      $this->clientRepository->update($client->getId(), $client->getName(), $client->getContact(),  $client->getCreatedAt()->format(DateTime::ATOM), $client->getUpdatedAt()->format(DateTime::ATOM));

      // ! lock to make read only
      $client->lock();

      return $client->getRaw();
    } catch (\Throwable $th) {
      if ($th instanceof Failure) {
        return $th;
      } else {
        return new ServerFailure();
      }
    }
  }

  public function getRepository()
  {
    return $this->clientRepository;
  }
}