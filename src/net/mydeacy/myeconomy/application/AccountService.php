<?php
declare(strict_types=1);

namespace net\mydeacy\myeconomy\application;

use net\mydeacy\myeconomy\domain\Account;
use net\mydeacy\myeconomy\domain\AccountRepository;
use net\mydeacy\myeconomy\event\account\AccountCreateEvent;
use net\mydeacy\myeconomy\event\money\AddMoneyEvent;
use net\mydeacy\myeconomy\event\money\MoneyChangedEvent;
use net\mydeacy\myeconomy\event\money\PayMoneyEvent;
use net\mydeacy\myeconomy\event\money\ReduceMoneyEvent;
use net\mydeacy\myeconomy\event\money\SetMoneyEvent;
use function min;

/**
 * Account service.
 */
final class AccountService {

	private AccountRepository $repository;

	private int $defaultMoney;

	private int $maxMoney;

	/**
	 * Creates a new instance.
	 *
	 * @param AccountRepository $repository Repository.
	 * @param int $defaultMoney Default money.
	 * @param int $maxMoney Max money.
	 */
	public function __construct(AccountRepository $repository, int $defaultMoney, int $maxMoney) {
		$this->repository = $repository;
		$this->defaultMoney = max(0, $defaultMoney);
		$this->maxMoney = max(0, $maxMoney);
	}

	/**
	 * Creates a player account.
	 *
	 * @param string $name Name.
	 * @param ?int $defaultMoney Default money.
	 * @param string $issuer Issuer.
	 * @param bool $force Force.
	 *
	 * @return bool True on success.
	 */
	public function createAccount(string $name, ?int $defaultMoney, string $issuer, bool $force) :bool {
		$normalizedName = Account::normalizeName($name);
		$resolvedDefaultMoney = $defaultMoney ?? $this->defaultMoney;
		$clampedMoney = $this->clampAmount($resolvedDefaultMoney);
		return $this->repository->transaction(function(AccountRepository $repository) use (
			$normalizedName,
			$clampedMoney,
			$issuer,
			$force
		) :bool {
			if ($repository->findBalance($normalizedName) !== null) {
				return false;
			}
			$event = new AccountCreateEvent($normalizedName, $clampedMoney, $issuer);
			$event->call();
			if ($event->isCancelled() && !$force) {
				return false;
			}
			$initial = $this->clampAmount($event->getDefaultMoney());
			return $repository->insert($normalizedName, $initial);
		});
	}

	/**
	 * Checks if an account exists.
	 *
	 * @param string $name Name.
	 *
	 * @return bool True on success.
	 */
	public function accountExists(string $name) :bool {
		$normalizedName = Account::normalizeName($name);
		return $this->repository->findBalance($normalizedName) !== null;
	}

	/**
	 * Returns balance.
	 *
	 * @param string $name Name.
	 *
	 * @return ?int Value or null if not available.
	 */
	public function getBalance(string $name) :?int {
		$normalizedName = Account::normalizeName($name);
		return $this->repository->findBalance($normalizedName);
	}

	/**
	 * Sets money.
	 *
	 * @param string $name Name.
	 * @param int $money Money amount.
	 * @param string $issuer Issuer.
	 * @param bool $force Force.
	 *
	 * @return MoneyOperationResult
	 */
	public function setMoney(string $name, int $money, string $issuer, bool $force) :MoneyOperationResult {
		$normalizedName = Account::normalizeName($name);
		if (!$this->isValidAmount($money)) {
			return new MoneyOperationResult(ResultCode::RET_INVALID);
		}
		return $this->repository->transaction(function(AccountRepository $repository) use (
			$normalizedName,
			$money,
			$issuer,
			$force
		) :MoneyOperationResult {
			$old = $repository->findBalance($normalizedName);
			if ($old === null) {
				return new MoneyOperationResult(ResultCode::RET_NO_ACCOUNT);
			}
			$event = new SetMoneyEvent($normalizedName, $money, $issuer);
			$event->call();
			if ($event->isCancelled() && !$force) {
				return new MoneyOperationResult(ResultCode::RET_CANCELLED);
			}
			$repository->updateBalance($normalizedName, $money);
			(new MoneyChangedEvent($normalizedName, $money, $old, $issuer))->call();
			return new MoneyOperationResult(ResultCode::RET_SUCCESS, $old, $money);
		});
	}

	/**
	 * Adds money.
	 *
	 * @param string $name Name.
	 * @param int $amount Amount.
	 * @param string $issuer Issuer.
	 * @param bool $force Force.
	 *
	 * @return MoneyOperationResult
	 */
	public function addMoney(string $name, int $amount, string $issuer, bool $force) :MoneyOperationResult {
		$normalizedName = Account::normalizeName($name);
		if (!$this->isValidAmount($amount)) {
			return new MoneyOperationResult(ResultCode::RET_INVALID);
		}
		return $this->repository->transaction(function(AccountRepository $repository) use (
			$normalizedName,
			$amount,
			$issuer,
			$force
		) :MoneyOperationResult {
			$old = $repository->findBalance($normalizedName);
			if ($old === null) {
				return new MoneyOperationResult(ResultCode::RET_NO_ACCOUNT);
			}
			$new = $old + $amount;
			if (!$this->isValidAmount($new)) {
				return new MoneyOperationResult(ResultCode::RET_INVALID);
			}
			$event = new AddMoneyEvent($normalizedName, $amount, $issuer);
			$event->call();
			if ($event->isCancelled() && !$force) {
				return new MoneyOperationResult(ResultCode::RET_CANCELLED);
			}
			$repository->updateBalance($normalizedName, $new);
			(new MoneyChangedEvent($normalizedName, $new, $old, $issuer))->call();
			return new MoneyOperationResult(ResultCode::RET_SUCCESS, $old, $new);
		});
	}

	/**
	 * Reduces money.
	 *
	 * @param string $name Name.
	 * @param int $amount Amount.
	 * @param string $issuer Issuer.
	 * @param bool $force Force.
	 *
	 * @return MoneyOperationResult
	 */
	public function reduceMoney(string $name, int $amount, string $issuer, bool $force) :MoneyOperationResult {
		$normalizedName = Account::normalizeName($name);
		if (!$this->isValidAmount($amount)) {
			return new MoneyOperationResult(ResultCode::RET_INVALID);
		}
		return $this->repository->transaction(function(AccountRepository $repository) use (
			$normalizedName,
			$amount,
			$issuer,
			$force
		) :MoneyOperationResult {
			$old = $repository->findBalance($normalizedName);
			if ($old === null) {
				return new MoneyOperationResult(ResultCode::RET_NO_ACCOUNT);
			}
			if ($old - $amount < 0) {
				return new MoneyOperationResult(ResultCode::RET_INVALID, $old, $old);
			}
			$new = $old - $amount;
			$event = new ReduceMoneyEvent($normalizedName, $amount, $issuer);
			$event->call();
			if ($event->isCancelled() && !$force) {
				return new MoneyOperationResult(ResultCode::RET_CANCELLED);
			}
			$repository->updateBalance($normalizedName, $new);
			(new MoneyChangedEvent($normalizedName, $new, $old, $issuer))->call();
			return new MoneyOperationResult(ResultCode::RET_SUCCESS, $old, $new);
		});
	}

	/**
	 * Transfers money.
	 *
	 * @param string $payer Payer name.
	 * @param string $target Target name.
	 * @param int $amount Amount.
	 * @param string $issuer Issuer.
	 * @param bool $force Force.
	 *
	 * @return MoneyOperationResult
	 */
	public function payMoney(
		string $payer,
		string $target,
		int $amount,
		string $issuer,
		bool $force
	) :MoneyOperationResult {
		$normalizedPayer = Account::normalizeName($payer);
		$normalizedTarget = Account::normalizeName($target);
		if ($normalizedPayer === $normalizedTarget) {
			return new MoneyOperationResult(ResultCode::RET_INVALID);
		}
		if (!$this->isValidAmount($amount)) {
			return new MoneyOperationResult(ResultCode::RET_INVALID);
		}
		return $this->repository->transaction(function(AccountRepository $repository) use (
			$normalizedPayer,
			$normalizedTarget,
			$amount,
			$issuer,
			$force
		) :MoneyOperationResult {
			$payerBalance = $repository->findBalance($normalizedPayer);
			$targetBalance = $repository->findBalance($normalizedTarget);
			if ($payerBalance === null || $targetBalance === null) {
				return new MoneyOperationResult(ResultCode::RET_NO_ACCOUNT);
			}
			if ($payerBalance - $amount < 0) {
				return new MoneyOperationResult(ResultCode::RET_INVALID, $payerBalance, $payerBalance);
			}
			$event = new PayMoneyEvent($normalizedPayer, $normalizedTarget, $amount, $issuer);
			$event->call();
			if ($event->isCancelled() && !$force) {
				return new MoneyOperationResult(ResultCode::RET_CANCELLED);
			}
			$newPayer = $payerBalance - $amount;
			$newTarget = $targetBalance + $amount;
			if (!$this->isValidAmount($newTarget)) {
				return new MoneyOperationResult(ResultCode::RET_INVALID);
			}
			$repository->updateBalance($normalizedPayer, $newPayer);
			$repository->updateBalance($normalizedTarget, $newTarget);
			(new MoneyChangedEvent($normalizedPayer, $newPayer, $payerBalance, $issuer))->call();
			(new MoneyChangedEvent($normalizedTarget, $newTarget, $targetBalance, $issuer))->call();
			return new MoneyOperationResult(ResultCode::RET_SUCCESS, $payerBalance, $newPayer);
		});
	}

	/**
	 * Returns all balances keyed by player name.
	 *
	 * @return array<string, int>
	 */
	public function getAllBalances() :array {
		return $this->repository->listAll();
	}

	/**
	 * Returns a sorted page of balances.
	 *
	 * @return array<string, int>
	 */
	public function getTopBalances(int $limit, int $offset, array $excludeNames = []) :array {
		$normalizedOffset = max(0, $offset);
		if ($limit <= 0) {
			return [];
		}
		if ($excludeNames === []) {
			return $this->repository->listTop($limit, $normalizedOffset, []);
		}
		$exclude = [];
		foreach ($excludeNames as $name) {
			$exclude[] = Account::normalizeName((string)$name);
		}
		return $this->repository->listTop($limit, $normalizedOffset, $exclude);
	}

	/**
	 * Counts accounts.
	 *
	 * @param array $excludeNames Exclude names.
	 *
	 * @return int Count value.
	 */
	public function countAccounts(array $excludeNames = []) :int {
		if ($excludeNames === []) {
			return $this->repository->countAll();
		}
		$exclude = [];
		foreach ($excludeNames as $name) {
			$exclude[] = Account::normalizeName((string)$name);
		}
		return $this->repository->countAll($exclude);
	}

	/**
	 * Returns total balance.
	 *
	 * @return int
	 */
	public function getTotalBalance() :int {
		return $this->repository->sumBalances();
	}

	private function clampAmount(int $amount) :int {
		return max(0, min($amount, $this->maxMoney));
	}

	private function isValidAmount(int $amount) :bool {
		return $amount >= 0 && $amount <= $this->maxMoney;
	}
}
