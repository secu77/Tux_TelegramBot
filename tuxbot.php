<?php

define('TELEGRAMWEB', 'https://api.telegram.org/bot');
define('TOKEN', '<TOKEN>');
define('TELTIMEOUT', 20);
define('MYID', '<MASTERID>');
define('AUTOR', 'SECURY');


Class Bot {

  /*Funcion Principal*/
  public function run(){
    $last_msg_id = 337031299;

    while(true){
      $content = $this->getUpdates($last_msg_id);
      $decoded = json_decode($content,true);

      /*
       * El mensaje de respuesta al GetUpdates falla, el json no es válido o no hay.
       */
      if( (!isset($decoded)) || (!isset($decoded['ok'])) || (!$decoded['ok']) ){
        sleep(2);
        continue;
      }
      
      /*
       * Recorriendo el array resultante del json en busca de nuevos mensajes.
       */
      foreach ($decoded['result'] as $update) {
        if( (!empty($update['update_id'])) && ($update['update_id'] > $last_msg_id) ){
          $last_msg_id = $update['update_id'];
          $textSP = null;

          /*
           * Validando para las siguientes posibilidades:
           * 
           *   -Para SuperGrupos: Nuevo participante en supergrupo.
           *   -Para Mensaje Privado: Conversación Privada.
           */
          if(isset($update['message']['new_chat_participant']) && (strcmp($update['message']['chat']['type'], "supergroup") == 0)){  
            
            /*
             * Formando la estructura del registro de los logs, para mantener el control de quien en que grupo.
             */
            echo "\n[" . date("Y-m-d H:i:s") . "]\n> " . $update['message']['from']['first_name'] . " (" . $update['message']['from']['id'] . ") : Se unió al grupo ".$update['message']['chat']['title']." \n";

            /*
             * Procesando la respuesta para el supergrupo, por defecto el mensaje de Bienvenida a SUA.
             */
            $textSP = "\n👋🏻Bienvenido/a ".$update['message']['from']['username']."!🎉\nAquí tienes el codigo ético:\nhttps://stationx11.es/code_of_conduct.html\nNo olvides echarle un vistazo a la descripción!😈";
            $this->sendMessage($update['message']['chat']['id'],$textSP,'HTML',true,false,null,null);

          }elseif(strcmp($update['message']['chat']['type'], "private") == 0){
            
            /*
             * Seguimos con los logs, esta vez para los chats privados, más importante por temas de seguridad.
             */
            echo "\n[" . date("Y-m-d H:i:s") . "]\n> " . $update['message']['from']['first_name'] . " (" . $update['message']['from']['id'] . ") : Mando por privado: '".$update['message']['text']."' \n";

            /*
             * Procesando la respuesta para los chats privados, multiples posibilidades
             */
            $sendData = $this->valCommand($update['message']['chat']['id'],$update['message']['from']['first_name'], $update['message']['text']);
            $this->sendMessage($sendData['id'],$sendData['text'],$sendData['parse'],true,false,null,null);
          }
        }
      }
    }
  }



  /*Funcion para obtener mensajes de la API de telegram mediante el getUpdates*/
  public function getUpdates($last_msg_id){
    $ch = curl_init();
    $options = array(
        CURLOPT_URL => TELEGRAMWEB.TOKEN."/getUpdates",
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
          'offset' => $last_msg_id + 1,
          'limit' => 100,
          'timeout' => TELTIMEOUT,
        ]
    );
    curl_setopt_array($ch, $options);

    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
  }


  /*Funcion para mandar el mensaje mediante la Api de Telegram al destinatario*/
  public function sendMessage($id,$text,$parse,$nowebprev,$silencenot,$replyto,$markup){
    $ch = curl_init();
    $options = array(
        CURLOPT_URL => TELEGRAMWEB.TOKEN."/sendMessage",
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
          'chat_id' => $id,
          'text' => $text,
          'parse_mode' => $parse,
          'disable_web_page_preview' => $nowebprev,
          'disable_notification' => $silencenot,
          'reply_to_message_id' => $replyto,
        ]
    );
    curl_setopt_array($ch, $options);

    curl_exec($ch);
    curl_close($ch);
  }


  /*Función que valida los mensajes privados al bot*/
  public function valCommand($id,$username,$text){
    $response = ['id' => $id,'text' => null, 'parse' => null];

    if(strcmp($text, "/start") == 0){
      /*
       *  Mensaje de Bienvenida al inicio del bot.
       */
      $response['text'] = "\nHola ".$username.", aún no estoy programado para atenderte, nos vemos pronto!\n";
      $response['parse'] = "HTML";
  
    }else{
      /*
       *  Mensaje por defecto para un comando no validado.
       */
      $response['text'] = "\nNot Working now\n";
      $response['parse'] = "HTML";
    }

    return $response;
  }

} // Final de la Clase Principal


$bot = new Bot;
$bot->run();

?>
