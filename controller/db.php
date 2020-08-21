<?php
class DB{
   private static $dbConnection; 

   public static function conectarDB(){
      if(self::$dbConnection === null){
          self::$dbConnection = 
            new PDO('mysql:dbname=reservas;host=localhost;port=3308;charset=utf8',
                    'reservasUser', 'reservasUser');
            self::$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            return self::$dbConnection;
      } 
   }
}
