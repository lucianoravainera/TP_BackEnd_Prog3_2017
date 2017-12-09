<?php


class MWparaCORS
{

	public function HabilitarCORSTodos($request, $response, $next) {
		 $response = $next($request, $response);
   		 return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
	}

	public function HabilitarCORS8080($request, $response, $next) {
		 $response = $next($request, $response);
   		 return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:8080')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
	}

	public function HabilitarCORS4200($request, $response, $next) {
		 $response = $next($request, $response);
   		 return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:4200')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
	}

}