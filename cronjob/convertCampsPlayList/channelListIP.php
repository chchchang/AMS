<?php
/*2022/06/07 測式用的channelList.txt 插入 IP位置使用,channelList.txt來源為CAMPS的barker/channel?orbitonly查詢API
 */

$exect = new insertIpToChannelList();
$exect->hadle();

class insertIpToChannelList{
    private $ipmap;//儲存orbit playout id和IP:PORT的對照表，[playout_id=>channel_ip]
    private $logWriter;
    private $readfileName;

    function __construct() {
        $this->ipmap = array(
            5=>"230.1.2.117:2117",
            4=>"230.1.2.102:2102",
            24=>"224.1.4.127:11111",
            8=>"230.1.2.104:2104",
            9=>"230.1.2.109:2109",
            15=>"230.1.2.111:2111",
            10=>"230.1.2.114:2114",
            11=>"230.1.2.115:2115",
            16=>"230.1.2.116:2116",
            19=>"230.1.2.119:2119",
            18=>"230.1.2.75:2075",
            20=>"230.1.2.122:2122",
            21=>"230.1.2.106:2106",
            13=>"230.1.2.121:2121",
            14=>"230.1.2.101:2101",
            7=>"230.1.2.126:2126",
            6=>"230.1.2.103:2103"
        );
         $this->readfileName = "channelList.txt";
        /*$this->playListReader = fopen("data/".$readfile,"r");

        if(!$this->playListReader){
            $this->dolog("cant open file:".$readfile);
            exit();
        }*/
    }

    function __destruct()
    {
        //fclose($this->playListReader);
    }

    private function dolog($line){
        $message = date('Y-m-d h:i:s').$line."\n";
        echo $message;
        //fwrite($this->logWriter,$message);
    }

    public function hadle($date=''){
        if($date == '')
            $date = date('Y-m-d');
        $this->dolog("------start-----");
        $this->dolog("date.".$date);

        $channellist = json_decode(file_get_contents($this->readfileName),true);//dev
        foreach($channellist as $i=>$channelData){
            $ip = "";
            if(isset( $this->ipmap[$channelData["playout_id"]])){
                $ip =  $this->ipmap[$channelData["playout_id"]];
            }
            $channellist[$i]["ip"]=$ip;
        }

        $writer = fopen("channelListWithIP.txt","w");
        $output = json_encode($channellist);
        fwrite($writer,$output);
        fclose($writer);
    }    
}

?>