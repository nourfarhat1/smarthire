<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

#[AsCommand(
    name: 'app:update-user',
    description: 'Updates user password and roles',
)]
class UpdateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail('farhat.nour@esprit.tn');

        if (!$user) {
            $output->writeln('User not found!');
            return Command::FAILURE;
        }

        // Set roles
        $user->setRoles(['ROLE_USER']);
        
        // Set the password as plain text
        $plainPassword = '231jft';
        $user->setPassword($plainPassword);
        
        // Save to database
        $this->entityManager->flush();
        
        $output->writeln('User updated successfully!');
        return Command::SUCCESS;
    }
}
