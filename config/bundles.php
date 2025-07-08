<?php

return [
	Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => [ 'all' => true ],
	Symfony\Bundle\TwigBundle\TwigBundle::class => [ 'all' => true ],
	Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => [ 'all' => true ],
	Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => [ 'all' => true ],
	Wikimedia\ToolforgeBundle\ToolforgeBundle::class => [ 'all' => true ],
	Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => [ 'dev' => true, 'test' => true ],
	Symfony\Bundle\MonologBundle\MonologBundle::class => [ 'all' => true ],
	Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => [ 'all' => true ],
	DAMA\DoctrineTestBundle\DAMADoctrineTestBundle::class => [ 'test' => true ],
	League\FlysystemBundle\FlysystemBundle::class => [ 'all' => true ],
];
