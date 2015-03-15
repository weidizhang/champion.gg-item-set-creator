<?php
class ChampionGG {
	public function getAllSets() {
		echo "Creating item sets for all champions...\n";
		$saveFolder = time() . "_ItemSets";
		$page = $this->getPage("http://champion.gg/");
		preg_match_all('/<a href="([^"]*)" style="display:block">/si', $page, $list);
		foreach ($list[1] as $champPage) {
			$data = explode("/", $champPage);
			$champ = $data[2];
			$role = $data[3];

			$this->getOneSet($champ, $role, $saveFolder);
		}
		echo "Complete!\n";
		return true;
	}

	public function getOneSet($champ, $role, $saveFolder = null) {
		$url = "http://champion.gg/champion/" . $champ . "/" . $role;

		$page = $this->getPage($url);
		$data = $this->getBetween($page, "matchupData.championData = ", ";");
		$champJSON = json_decode($data, true);
		$currentPatch = $this->getBetween($page, "<small>Patch <strong>", "</strong>");				

		$firstMG = $champJSON["firstItems"]["mostGames"];
		$firstHWP = $champJSON["firstItems"]["highestWinPercent"];
		$fullMG = $champJSON["items"]["mostGames"];
		$fullHWP = $champJSON["items"]["highestWinPercent"];

		if (!isset($firstMG["games"], $firstHWP["games"], $fullMG["games"], $fullHWP["games"])) {
			echo "Woops, full data is unavailable for " . $champ . " in " . $role . " role\n";
			return false;
		}		

		$consumeItems = array(2003, 2004, 2044, 2043, 2041, 2138, 2137, 2139, 2140);
		$trinketItems = array(3340, 3341, 3342);

		$firstMGBlock = array(
			"items" => array_merge($this->getItems($firstMG), $this->getItems($trinketItems, true)),
			"type" => "Most Frequent Starters (" . $firstMG["winPercent"] . "% wins over " . $firstMG["games"] . " games)"
		);
		$firstHWPBlock = array(
			"items" => array_merge($this->getItems($firstHWP), $this->getItems($trinketItems, true)),
			"type" => "Highest Win Rate Starters (" . $firstHWP["winPercent"] . "% wins over " . $firstHWP["games"] . " games)"
		);
		$fullMGBlock = array(
			"items" => $this->getItems($fullMG),
			"type" => "Most Frequent Build (" . $fullMG["winPercent"] . "% wins over " . $fullMG["games"] . " games)"
		);
		$fullHWPBlock = array(
			"items" => $this->getItems($fullHWP),
			"type" => "Highest Win Rate Build (" . $fullHWP["winPercent"] . "% wins over " . $fullHWP["games"] . " games)"
		);
		$consumeBlock = array(
			"items" => $this->getItems($consumeItems, true),
			"type" => "Consumables"
		);

		$roleFormatted = substr($champJSON["role"], 0, 1) . substr(strtolower($champJSON["role"]), 1);
		$itemSetArr = array(
			"map" => "any",
			"isGlobalForChampions" => false,
			"blocks" => array(
				$firstMGBlock,
				$firstHWPBlock,
				$fullMGBlock,
				$fullHWPBlock,
				$consumeBlock
			),
			"associatedChampions" => array(),
			"title" => $roleFormatted . " " . $currentPatch,
			"priority" => false,
			"mode" => "any",
			"isGlobalForMaps" => true,
			"associatedMaps" => array(),
			"type" => "custom",
			"sortrank" => 1,
			"champion" => $champJSON["key"]
		);

		if ($saveFolder == null) {
			$saveFolder = $champJSON["key"] . "/Recommended";
		}
		else {
			$saveFolder = $saveFolder . "/" . $champJSON["key"] . "/Recommended";
		}
		
		if (!file_exists($saveFolder)) {
			mkdir($saveFolder, 0777, true);
		}
		$fileName = str_replace(".", "_", $currentPatch) . "_" . $roleFormatted . ".json";
		$fileName = $saveFolder . "/" . $fileName;
		$itemSetJSON = json_encode($itemSetArr, JSON_PRETTY_PRINT);
		file_put_contents($fileName, $itemSetJSON);
		echo "Saved set for " . $champ . " in " . $role . " role to: " . $fileName . "\n";
		return true;
	}

	private function getItems($array, $fromPreset = false) {
		$items = array();
		if ($fromPreset) {
			foreach ($array as $item) {
				$items[] = array(
					"count" => 1,
					"id" => (string) $item
				);
			}
		}
		else {
			$itemIDs = array();
			foreach ($array["items"] as $item) {
				$id = $item["id"];
				if ($id == 2010) {
					$id = 2003;
				}
				if (isset($itemIDs[$id])) {
					$itemIDs[$id]++;
				}
				else {
					$itemIDs[$id] = 1;
				}
			}

			foreach ($itemIDs as $itemID => $count) {
				$items[] = array(
					"count" => $count,
					"id" => (string) $itemID
				);
			}
		}
		return $items;
	}

	private function getBetween($content, $start, $end){
		$r = explode($start, $content);
		if (isset($r[1])) {
			$r = explode($end, $r[1]);
			return $r[0];
		}
		return '';
	}

	private function getPage($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/600.3.18 (KHTML, like Gecko) Version/8.0.3 Safari/600.3.18");
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$data = curl_exec($ch);
		return $data;
	}
}

$champ = new ChampionGG();
//$champ->getOneSet("Zed", "Middle");
$champ->getAllSets();
?>