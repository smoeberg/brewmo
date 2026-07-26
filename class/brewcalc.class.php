<?php
class BrewmoCalc
{
    public static function abv($og_sg, $fg_sg)
    {
        if ($og_sg <= 0 || $fg_sg <= 0) return null;
        return max(0, ($og_sg - $fg_sg) * 131.25);
    }
}
