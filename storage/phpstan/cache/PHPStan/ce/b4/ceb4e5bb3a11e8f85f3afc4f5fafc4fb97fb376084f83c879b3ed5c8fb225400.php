<?php declare(strict_types = 1);

// odsl-/var/www/html/app
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1-enums',
   'data' => 
  array (
    '/var/www/html/app/Contracts/Repositories/ObservationRepositoryInterface.php' => 
    array (
      0 => '21c29d565dc2a99c84529501dcd47b60d351d760fa0f962681b08e848052fe05',
      1 => 
      array (
        0 => 'app\\contracts\\repositories\\observationrepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\contracts\\repositories\\create',
        1 => 'app\\contracts\\repositories\\findbyid',
        2 => 'app\\contracts\\repositories\\paginatepublished',
        3 => 'app\\contracts\\repositories\\findpublishedbyid',
        4 => 'app\\contracts\\repositories\\update',
        5 => 'app\\contracts\\repositories\\delete',
        6 => 'app\\contracts\\repositories\\paginatebyuser',
        7 => 'app\\contracts\\repositories\\paginateall',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Contracts/Repositories/ResourceRepositoryInterface.php' => 
    array (
      0 => 'ef2e4944ca305a2030bd0d509ca62beddca2afa34f81577b2c62107e3213fcc8',
      1 => 
      array (
        0 => 'app\\contracts\\repositories\\resourcerepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\contracts\\repositories\\createforresourceable',
        1 => 'app\\contracts\\repositories\\deleteforresourceable',
        2 => 'app\\contracts\\repositories\\deletebyid',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Contracts/Repositories/UserRepositoryInterface.php' => 
    array (
      0 => '3aeeb55d30663abccb6b1b2028a5e981902dd40722047f202021bfd50b63594c',
      1 => 
      array (
        0 => 'app\\contracts\\repositories\\userrepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\contracts\\repositories\\paginateall',
        1 => 'app\\contracts\\repositories\\findbyid',
        2 => 'app\\contracts\\repositories\\update',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Enums/ResourceType.php' => 
    array (
      0 => '8f92248d383ac35c5be102204ea128c2590eb315070c487019c65f0699984b6f',
      1 => 
      array (
        0 => 'app\\enums\\resourcetype',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Http/Controllers/AdminDashboardController.php' => 
    array (
      0 => '823457be4e51187da213b2762d495ab5d1c386a8602cc95f342c7478a0f862f7',
      1 => 
      array (
        0 => 'app\\http\\controllers\\admindashboardcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\__invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Http/Controllers/AdminObservationController.php' => 
    array (
      0 => '82904d83557b1682681089b6430a6df159507989158fb20cf7b09d7779fea8a0',
      1 => 
      array (
        0 => 'app\\http\\controllers\\adminobservationcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\__construct',
        1 => 'app\\http\\controllers\\index',
        2 => 'app\\http\\controllers\\destroy',
        3 => 'app\\http\\controllers\\unpublish',
        4 => 'app\\http\\controllers\\republish',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Http/Controllers/AdminUserController.php' => 
    array (
      0 => '087c283b78c02e805b519679e4cd73e817ce50dbb2936475463cd1fa06ae2f1f',
      1 => 
      array (
        0 => 'app\\http\\controllers\\adminusercontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\__construct',
        1 => 'app\\http\\controllers\\index',
        2 => 'app\\http\\controllers\\block',
        3 => 'app\\http\\controllers\\unblock',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Http/Controllers/AuthController.php' => 
    array (
      0 => '8a6bb453eb7d0c73fb416d30dcd4af089b321b330567402df37d7ea126a73365',
      1 => 
      array (
        0 => 'app\\http\\controllers\\authcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\__construct',
        1 => 'app\\http\\controllers\\createlogin',
        2 => 'app\\http\\controllers\\createregister',
        3 => 'app\\http\\controllers\\storelogin',
        4 => 'app\\http\\controllers\\storeregister',
        5 => 'app\\http\\controllers\\destroy',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Http/Controllers/Controller.php' => 
    array (
      0 => 'e5ddafa07059bfc9f8310767b0fc04dd3b8a1f50bcec1fd693b19f5555697825',
      1 => 
      array (
        0 => 'app\\http\\controllers\\controller',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Http/Controllers/ObservationController.php' => 
    array (
      0 => 'd19cd8aba9d7317b4249ea96a2ffb5a6975fdd1ae039e4f73b2749264b9ee773',
      1 => 
      array (
        0 => 'app\\http\\controllers\\observationcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\__construct',
        1 => 'app\\http\\controllers\\index',
        2 => 'app\\http\\controllers\\myobservations',
        3 => 'app\\http\\controllers\\create',
        4 => 'app\\http\\controllers\\store',
        5 => 'app\\http\\controllers\\show',
        6 => 'app\\http\\controllers\\edit',
        7 => 'app\\http\\controllers\\update',
        8 => 'app\\http\\controllers\\destroy',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Http/Middleware/EnsureUserIsAdmin.php' => 
    array (
      0 => '204b73785a28dd315ec9d68c1161cb6cc0ee355f31fb5caab8bce9fac8c0ea41',
      1 => 
      array (
        0 => 'app\\http\\middleware\\ensureuserisadmin',
      ),
      2 => 
      array (
        0 => 'app\\http\\middleware\\handle',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Http/Middleware/EnsureUserIsNotBlocked.php' => 
    array (
      0 => 'ee9867e6885d81334c3e3ce05af964abf74fe8350806d49cc06a013615f7139e',
      1 => 
      array (
        0 => 'app\\http\\middleware\\ensureuserisnotblocked',
      ),
      2 => 
      array (
        0 => 'app\\http\\middleware\\handle',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Http/Requests/StoreObservationRequest.php' => 
    array (
      0 => '0ab392ab5073d51f24d8eb6dfcbeaa9bd5a216c47a18cf6faaf2e581a531ea1b',
      1 => 
      array (
        0 => 'app\\http\\requests\\storeobservationrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Http/Requests/UpdateObservationRequest.php' => 
    array (
      0 => 'd73a04b2c60cedf1976635792952ce08853350200ce758c8829bdfdeb25f4cdf',
      1 => 
      array (
        0 => 'app\\http\\requests\\updateobservationrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
        2 => 'app\\http\\requests\\withvalidator',
        3 => 'app\\http\\requests\\validateatleastonephotoremains',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Models/Observation.php' => 
    array (
      0 => '935f3e937201e7d3ced2fbc5327fc9aebdf6a805e13a511a649d14e2a9c9d95d',
      1 => 
      array (
        0 => 'app\\models\\observation',
      ),
      2 => 
      array (
        0 => 'app\\models\\casts',
        1 => 'app\\models\\user',
        2 => 'app\\models\\resources',
        3 => 'app\\models\\photos',
        4 => 'app\\models\\videos',
        5 => 'app\\models\\scopepublished',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Models/Resource.php' => 
    array (
      0 => 'a901043fd9a8b8fa17f2ee965d587391570b25f779fb74635d689ca202a824a5',
      1 => 
      array (
        0 => 'app\\models\\resource',
      ),
      2 => 
      array (
        0 => 'app\\models\\casts',
        1 => 'app\\models\\resourceable',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Models/Role.php' => 
    array (
      0 => '61979c2fafc06320b99e89af98b7b4c4b42752a52c20c1c74efc9232756902ef',
      1 => 
      array (
        0 => 'app\\models\\role',
      ),
      2 => 
      array (
        0 => 'app\\models\\users',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Models/User.php' => 
    array (
      0 => '65ab3776d9ddde1ad5d1c0cecb92195c7ea6ab98c3535ab4aec27e1cae7c7db6',
      1 => 
      array (
        0 => 'app\\models\\user',
      ),
      2 => 
      array (
        0 => 'app\\models\\casts',
        1 => 'app\\models\\role',
        2 => 'app\\models\\isadmin',
        3 => 'app\\models\\isblocked',
        4 => 'app\\models\\observations',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Policies/ObservationPolicy.php' => 
    array (
      0 => '39a38c5ae4a122dd0691121079dc3b3247c8209e9507ac6111aff7eb5c098952',
      1 => 
      array (
        0 => 'app\\policies\\observationpolicy',
      ),
      2 => 
      array (
        0 => 'app\\policies\\update',
        1 => 'app\\policies\\delete',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Providers/AppServiceProvider.php' => 
    array (
      0 => '5938132cb868df8d39576fc5603a44ce65300df18e91604f71de13082cabbb23',
      1 => 
      array (
        0 => 'app\\providers\\appserviceprovider',
      ),
      2 => 
      array (
        0 => 'app\\providers\\register',
        1 => 'app\\providers\\boot',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Repositories/EloquentObservationRepository.php' => 
    array (
      0 => '456acbf8cfd874f797ff243a3bbc6a4c92bc630c4b5bb036cb11cf7176de43f9',
      1 => 
      array (
        0 => 'app\\repositories\\eloquentobservationrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\create',
        1 => 'app\\repositories\\findbyid',
        2 => 'app\\repositories\\paginatepublished',
        3 => 'app\\repositories\\findpublishedbyid',
        4 => 'app\\repositories\\update',
        5 => 'app\\repositories\\delete',
        6 => 'app\\repositories\\paginatebyuser',
        7 => 'app\\repositories\\paginateall',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Repositories/EloquentResourceRepository.php' => 
    array (
      0 => 'b9611d1fa5ed486ec947b76bcaf29447b2acdcc78c1e516fdc1fca0374497d2b',
      1 => 
      array (
        0 => 'app\\repositories\\eloquentresourcerepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\createforresourceable',
        1 => 'app\\repositories\\deleteforresourceable',
        2 => 'app\\repositories\\deletebyid',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Repositories/EloquentUserRepository.php' => 
    array (
      0 => 'a8cf819a44cf04c430a1eb0aafd0adf8dfe4cff632c60910ec7620c09ea9305e',
      1 => 
      array (
        0 => 'app\\repositories\\eloquentuserrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\paginateall',
        1 => 'app\\repositories\\findbyid',
        2 => 'app\\repositories\\update',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Services/AdminService.php' => 
    array (
      0 => 'e6362b40dfb97a30deb1225b9ff4fa046ee9dc8518c22894acf25696b6c36226',
      1 => 
      array (
        0 => 'app\\services\\adminservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\blockuser',
        2 => 'app\\services\\unblockuser',
        3 => 'app\\services\\unpublishobservation',
        4 => 'app\\services\\republishobservation',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Services/AuthService.php' => 
    array (
      0 => 'f663aabcc3d34ab8a362a5ae9bf31a793ab4ffceaa6e89cc943eac5a3dd5cd99',
      1 => 
      array (
        0 => 'app\\services\\authservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\register',
        1 => 'app\\services\\authenticate',
        2 => 'app\\services\\logout',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/app/Services/ObservationService.php' => 
    array (
      0 => 'ef7d95e76378d47ab0764e24517ee3d96153d91a41fa92a63bd5fe0acd8899ee',
      1 => 
      array (
        0 => 'app\\services\\observationservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\publishobservation',
        2 => 'app\\services\\getpublishedfeed',
        3 => 'app\\services\\findpublishedbyid',
        4 => 'app\\services\\findbyid',
        5 => 'app\\services\\getuserobservations',
        6 => 'app\\services\\getallobservations',
        7 => 'app\\services\\updateobservation',
        8 => 'app\\services\\deleteobservation',
        9 => 'app\\services\\removeresources',
        10 => 'app\\services\\storemedia',
      ),
      3 => 
      array (
      ),
    ),
  ),
));