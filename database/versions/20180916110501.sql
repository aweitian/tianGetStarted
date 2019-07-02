ALTER TABLE `task`
  ADD COLUMN `rank_first` INT NULL COMMENT '初排' AFTER `rank_id`,
  ADD COLUMN `rank_last` INT NULL COMMENT '新排' AFTER `rank_first`,
  ADD COLUMN `rank_change` INT NULL COMMENT '新排' AFTER `rank_last`;